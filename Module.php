<?php

/*
 * Copyright BibLibre, 2016-2017
 * Copyright Daniel Berthereau, 2017-2018
 *
 * This software is governed by the CeCILL license under French law and abiding
 * by the rules of distribution of free software.  You can use, modify and/ or
 * redistribute the software under the terms of the CeCILL license as circulated
 * by CEA, CNRS and INRIA at the following URL "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and rights to copy, modify
 * and redistribute granted by the license, users are provided only with a
 * limited warranty and the software's author, the holder of the economic
 * rights, and the successive licensors have only limited liability.
 *
 * In this respect, the user's attention is drawn to the risks associated with
 * loading, using, modifying and/or developing or reproducing the software by
 * the user in light of its specific status of free software, that may mean that
 * it is complicated to manipulate, and that also therefore means that it is
 * reserved for developers and experienced professionals having in-depth
 * computer knowledge. Users are therefore encouraged to load and test the
 * software's suitability as regards their requirements in conditions enabling
 * the security of their systems and/or data to be ensured and, more generally,
 * to use and operate it in the same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
 */

namespace Search;

use Omeka\Entity\Resource;
use Omeka\Module\AbstractModule;
use Omeka\Mvc\Controller\Plugin\Messenger;
use Omeka\Settings\SettingsInterface;
use Omeka\Stdlib\Message;
use Search\Form\ConfigForm;
use Search\Indexer\IndexerInterface;
use Zend\EventManager\Event;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\ModuleManager\ModuleManager;
use Zend\Mvc\Controller\AbstractController;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Renderer\PhpRenderer;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);
        $this->addAclRules();
        $this->addRoutes();
    }

    public function init(ModuleManager $moduleManager)
    {
        /** @var \Zend\ModuleManager\Listener\ServiceListenerInterface $serviceListerner */
        $serviceListener = $moduleManager->getEvent()->getParam('ServiceManager')
            ->get('ServiceListener');

        $serviceListener->addServiceManager(
            'Search\AdapterManager',
            'search_adapters',
            Feature\AdapterProviderInterface::class,
            'getSearchAdapterConfig'
        );
        $serviceListener->addServiceManager(
            'Search\FormAdapterManager',
            'search_form_adapters',
            Feature\FormAdapterProviderInterface::class,
            'getSearchFormAdapterConfig'
        );
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $this->setServiceLocator($serviceLocator);

        $messenger = new Messenger;
        $optionalModule = 'jQueryUI';
        if (!$this->isModuleActive($optionalModule)) {
            $messenger->addWarning('The module jQueryUI is required to customize the search pages.'); // @translate
        }
        $optionalModule = 'Reference';
        if (!$this->isModuleActive($optionalModule)) {
            $messenger->addWarning('The module Reference is required to use the facets with the default internal adapter.'); // @translate
        }

        $this->execSqlFromFile(__DIR__ . '/data/install/schema.sql');

        $this->manageConfig('install');
        $this->manageMainSettings('install');
        $this->manageSiteSettings('install');

        // TODO Move internal adapter in another module.
        // Create the internal adapter.
        $connection = $serviceLocator->get('Omeka\Connection');
        $sql = <<<'SQL'
INSERT INTO `search_index`
(`name`, `adapter`, `settings`, `created`)
VALUES
('Internal', 'internal', ?, NOW());
SQL;
        $sarchIndexSettings = ['resources' => ['items', 'item_sets']];
        $connection->executeQuery($sql, [
            json_encode($sarchIndexSettings, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
        $sql = <<<'SQL'
INSERT INTO `search_page`
(`index_id`, `name`, `path`, `form_adapter`, `settings`, `created`)
VALUES
('1', 'Internal', 'find', 'basic', ?, NOW());
SQL;
        $sarchPageSettings = require __DIR__ . '/config/adapter_internal.php';
        $connection->executeQuery($sql, [
            json_encode($sarchPageSettings, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
        $messenger->addNotice('The internal search engine is available. Enable it in the main settings (for admin) and in site settings (for public).'); // @translate
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $this->setServiceLocator($serviceLocator);
        $this->execSqlFromFile(__DIR__ . '/data/install/uninstall.sql');

        $this->manageConfig('uninstall');
        $this->manageMainSettings('uninstall');
        $this->manageSiteSettings('uninstall');
    }

    public function upgrade($oldVersion, $newVersion,
        ServiceLocatorInterface $serviceLocator)
    {
        $this->setServiceLocator($serviceLocator);
        require_once 'data/scripts/upgrade.php';
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach(
            '*',
            'view.layout',
            [$this, 'addHeadersAdmin']
        );

        $sharedEventManager->attach(
            \Omeka\Api\Adapter\ItemAdapter::class,
            'api.create.post',
            [$this, 'updateSearchIndex']
        );
        $sharedEventManager->attach(
            \Omeka\Api\Adapter\ItemAdapter::class,
            'api.update.post',
            [$this, 'updateSearchIndex']
        );
        $sharedEventManager->attach(
            \Omeka\Api\Adapter\ItemAdapter::class,
            'api.delete.post',
            [$this, 'updateSearchIndex']
        );

        $sharedEventManager->attach(
            \Omeka\Api\Adapter\ItemSetAdapter::class,
            'api.create.post',
            [$this, 'updateSearchIndex']
        );
        $sharedEventManager->attach(
            \Omeka\Api\Adapter\ItemSetAdapter::class,
            'api.update.post',
            [$this, 'updateSearchIndex']
        );
        $sharedEventManager->attach(
            \Omeka\Api\Adapter\ItemSetAdapter::class,
            'api.delete.post',
            [$this, 'updateSearchIndex']
        );

        $sharedEventManager->attach(
            \Omeka\Api\Adapter\MediaAdapter::class,
            'api.update.post',
            [$this, 'updateSearchIndexMedia']
        );
        $sharedEventManager->attach(
            \Omeka\Api\Adapter\MediaAdapter::class,
            'api.delete.pre',
            [$this, 'preUpdateSearchIndexMedia']
        );
        $sharedEventManager->attach(
            \Omeka\Api\Adapter\MediaAdapter::class,
            'api.delete.post',
            [$this, 'updateSearchIndexMedia']
        );

        $sharedEventManager->attach(
            \Omeka\Form\SettingForm::class,
            'form.add_elements',
            [$this, 'handleMainSettings']
        );
        $sharedEventManager->attach(
            \Omeka\Form\SettingForm::class,
            'form.add_input_filters',
            [$this, 'handleMainSettingsFilters']
        );
        $sharedEventManager->attach(
            \Omeka\Form\SiteSettingsForm::class,
            'form.add_elements',
            [$this, 'handleSiteSettings']
        );
        $sharedEventManager->attach(
            \Omeka\Form\SiteSettingsForm::class,
            'form.add_input_filters',
            [$this, 'handleSiteSettingsFilters']
        );
    }

    public function getConfigForm(PhpRenderer $renderer)
    {
        $services = $this->getServiceLocator();
        $config = $services->get('Config');
        $settings = $services->get('Omeka\Settings');
        $form = $services->get('FormElementManager')->get(ConfigForm::class);

        $data = [];
        $defaultSettings = $config[strtolower(__NAMESPACE__)]['config'];
        foreach ($defaultSettings as $name => $value) {
            $data[$name] = $settings->get($name, $value);
        }

        $form->init();
        $form->setData($data);
        $html = $renderer->formCollection($form, false);
        return $html;
    }

    public function handleConfigForm(AbstractController $controller)
    {
        $services = $this->getServiceLocator();
        $config = $services->get('Config');
        $space = strtolower(__NAMESPACE__);
        $settings = $services->get('Omeka\Settings');
        $form = $services->get('FormElementManager')->get(ConfigForm::class);

        $params = $controller->getRequest()->getPost();

        $form->init();
        $form->setData($params);
        if (!$form->isValid()) {
            $controller->messenger()->addErrors($form->getMessages());
            return false;
        }

        $params = $form->getData();
        $defaultSettings = $config[$space]['config'];
        $params = array_intersect_key($params, $defaultSettings);
        foreach ($params as $name => $value) {
            $settings->set($name, $value);
        }
    }

    protected function addAclRules()
    {
        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        $acl->allow(
            null,
            [
                \Search\Controller\IndexController::class,
                \Search\Api\Adapter\SearchPageAdapter::class,
                \Search\Api\Adapter\SearchIndexAdapter::class,
            ]
        );
        $acl->allow(
            null,
            [
                \Search\Entity\SearchPage::class,
                \Search\Entity\SearchIndex::class,
            ],
          'read'
        );
    }

    protected function addRoutes()
    {
        $services = $this->getServiceLocator();

        $router = $services->get('Router');
        if (!$router instanceof \Zend\Router\Http\TreeRouteStack) {
            return;
        }

        $settings = $services->get('Omeka\Settings');
        $adminSearchPages = $settings->get('search_pages', []);

        $api = $services->get('Omeka\ApiManager');
        $pages = $api->search('search_pages')->getContent();
        foreach ($pages as $page) {
            $pageId = $page->id();
            $pagePath = $page->path();
            $router->addRoute('search-page-' . $pageId, [
                'type' => \Zend\Router\Http\Segment::class,
                'options' => [
                    'route' => '/s/:site-slug/' . $pagePath,
                    'defaults' => [
                        '__NAMESPACE__' => 'Search\Controller',
                        '__SITE__' => true,
                        'controller' => \Search\Controller\IndexController::class,
                        'action' => 'search',
                        'id' => $pageId,
                    ],
                ],
            ]);

            if (in_array($pageId, $adminSearchPages)) {
                $router->addRoute('search-admin-page-' . $pageId, [
                    'type' => \Zend\Router\Http\Segment::class,
                    'options' => [
                        'route' => '/admin/' . $pagePath,
                        'defaults' => [
                            '__NAMESPACE__' => 'Search\Controller',
                            '__ADMIN__' => true,
                            'controller' => \Search\Controller\IndexController::class,
                            'action' => 'search',
                            'id' => $pageId,
                        ],
                    ],
                ]);
            }
        }
    }

    public function preUpdateSearchIndexMedia(Event $event)
    {
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $request = $event->getParam('request');
        $media = $api->read('media', $request->getId())->getContent();
        $data = $request->getContent();
        $data['itemId'] = $media->item()->id();
        $request->setContent($data);
    }

    public function updateSearchIndex(Event $event)
    {
        $serviceLocator = $this->getServiceLocator();
        $api = $serviceLocator->get('Omeka\ApiManager');

        $request = $event->getParam('request');
        $response = $event->getParam('response');
        $requestResource = $request->getResource();

        /** @var \Search\Api\Representation\SearchIndexRepresentation[] $searchIndexes */
        $searchIndexes = $api->search('search_indexes')->getContent();
        foreach ($searchIndexes as $searchIndex) {
            $searchIndexSettings = $searchIndex->settings();
            if (in_array($requestResource, $searchIndexSettings['resources'])) {
                $indexer = $searchIndex->indexer();
                if ($request->getOperation() == 'delete') {
                    $id = $request->getId();
                    $this->deleteIndexResource($indexer, $requestResource, $id);
                } else {
                    $resource = $response->getContent();
                    $this->updateIndexResource($indexer, $resource);
                }
            }
        }
    }

    public function updateSearchIndexMedia(Event $event)
    {
        $serviceLocator = $this->getServiceLocator();
        $api = $serviceLocator->get('Omeka\ApiManager');

        $request = $event->getParam('request');
        $response = $event->getParam('response');
        $itemId = $request->getValue('itemId');
        $item = $itemId
            ? $api->read('items', $itemId, [], ['responseContent' => 'resource'])->getContent()
            : $response->getContent()->getItem();

        /** @var \Search\Api\Representation\SearchIndexRepresentation[] $searchIndexes */
        $searchIndexes = $api->search('search_indexes')->getContent();
        foreach ($searchIndexes as $searchIndex) {
            $searchIndexSettings = $searchIndex->settings();
            if (in_array('items', $searchIndexSettings['resources'])) {
                $indexer = $searchIndex->indexer();
                $this->updateIndexResource($indexer, $item);
            }
        }
    }

    /**
     * Delete the search index for a resource.
     *
     * @param IndexerInterface $indexer
     * @param string $resourceName
     * @param int $id
     */
    protected function deleteIndexResource(IndexerInterface $indexer, $resourceName, $id)
    {
        try {
            $indexer->deleteResource($resourceName, $id);
        } catch (\Exception $e) {
            $services = $this->getServiceLocator();
            $logger = $services->get('Omeka\Logger');
            $logger->err(new Message('Unable to delete the search index for resource #%d: %s', // @translate
                $id, $e->getMessage()));
            $messenger = $services->get('ControllerPluginManager')->get('messenger');
            $messenger->addWarning(new Message('Unable to delete the search index for the deleted resource #%d: see log.', // @translate
                $id));
        }
    }

    /**
     * Update the search index for a resource.
     *
     * @param IndexerInterface $indexer
     * @param Resource $resource
     */
    protected function updateIndexResource(IndexerInterface $indexer, Resource $resource)
    {
        try {
            $indexer->indexResource($resource);
        } catch (\Exception $e) {
            $services = $this->getServiceLocator();
            $logger = $services->get('Omeka\Logger');
            $logger->err(new Message('Unable to index metadata of resource #%d for search: %s', // @translate
                $resource->getId(), $e->getMessage()));
            $messenger = $services->get('ControllerPluginManager')->get('messenger');
            $messenger->addWarning(new Message('Unable to update the search index for resource #%d: see log.', // @translate
                $resource->getId()));
        }
    }

    public function handleMainSettings(Event $event)
    {
        $this->handleAnySettings($event, 'settings');
    }

    public function handleSiteSettings(Event $event)
    {
        $this->handleAnySettings($event, 'site_settings');
    }

    public function handleMainSettingsFilters(Event $event)
    {
        $inputFilter = $event->getParam('inputFilter');
        $searchFilter = $inputFilter->get('search');
        $searchFilter->add([
            'name' => 'search_pages',
            'required' => false,
        ]);
        $searchFilter->add([
            'name' => 'search_main_page',
            'required' => false,
        ]);
        $searchFilter->add([
            'name' => 'search_api_page',
            'required' => false,
        ]);
    }

    public function handleSiteSettingsFilters(Event $event)
    {
        $inputFilter = $event->getParam('inputFilter');
        $searchFilter = $inputFilter->get('search');
        $searchFilter->add([
            'name' => 'search_pages',
            'required' => false,
        ]);
        $searchFilter->add([
            'name' => 'search_main_page',
            'required' => false,
        ]);
    }

    /**
     * Add the headers for admin management.
     *
     * @param Event $event
     */
    public function addHeadersAdmin(Event $event)
    {
        // Hacked, because the admin layout doesn't use a partial or a trigger
        // for the search engine.
        $view = $event->getTarget();
        // TODO How to attach all admin events only before 1.3?
        if ($view->params()->fromRoute('__SITE__')) {
            return;
        }
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $adminSearchPage = $settings->get('search_main_page');
        if (empty($adminSearchPage)) {
            return;
        }
        $view->headLink()->appendStylesheet($view->assetUrl('css/search-admin-search.css', 'Search'));
        $view->headScript()->appendScript(sprintf('var searchUrl = %s;', json_encode($adminSearchPage, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)));
        $view->headScript()->appendFile($view->assetUrl('js/search-admin-search.js', 'Search'));
    }

    /**
     * Execute a sql from a file.
     *
     * @param string $filepath
     * @return mixed
     */
    protected function execSqlFromFile($filepath)
    {
        if (!file_exists($filepath) || !filesize($filepath) || !is_readable($filepath)) {
            return;
        }
        $services = $this->getServiceLocator();
        $connection = $services->get('Omeka\Connection');
        $sql = file_get_contents($filepath);
        return $connection->exec($sql);
    }

    /**
     * Set or delete settings of the config of a module.
     *
     * @param string $process
     */
    protected function manageConfig($process)
    {
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');
        $this->manageAnySettings($settings, 'config', $process);
    }

    /**
     * Set or delete main settings.
     *
     * @param string $process
     */
    protected function manageMainSettings($process)
    {
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');
        $this->manageAnySettings($settings, 'settings', $process);
    }

    /**
     * Set or delete settings of all sites.
     *
     * @param string $process
     */
    protected function manageSiteSettings($process)
    {
        $settingsType = 'site_settings';
        $config = require __DIR__ . '/config/module.config.php';
        $space = strtolower(__NAMESPACE__);
        if (empty($config[$space][$settingsType])) {
            return;
        }
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings\Site');
        $api = $services->get('Omeka\ApiManager');
        $sites = $api->search('sites')->getContent();
        foreach ($sites as $site) {
            $settings->setTargetId($site->id());
            $this->manageAnySettings($settings, $settingsType, $process);
        }
    }

    /**
     * Set or delete all settings of a specific type.
     *
     * @param SettingsInterface $settings
     * @param string $settingsType
     * @param string $process
     */
    protected function manageAnySettings(SettingsInterface $settings, $settingsType, $process)
    {
        $config = require __DIR__ . '/config/module.config.php';
        $space = strtolower(__NAMESPACE__);
        if (empty($config[$space][$settingsType])) {
            return;
        }
        $defaultSettings = $config[$space][$settingsType];
        foreach ($defaultSettings as $name => $value) {
            switch ($process) {
                case 'install':
                    $settings->set($name, $value);
                    break;
                case 'uninstall':
                    $settings->delete($name);
                    break;
            }
        }
    }

    /**
     * Prepare a settings fieldset.
     *
     * @param Event $event
     * @param string $settingsType
     */
    protected function handleAnySettings(Event $event, $settingsType)
    {
        $services = $this->getServiceLocator();

        $settingsTypes = [
            // 'config' => 'Omeka\Settings',
            'settings' => 'Omeka\Settings',
            'site_settings' => 'Omeka\Settings\Site',
            // 'user_settings' => 'Omeka\Settings\User',
        ];
        if (!isset($settingsTypes[$settingsType])) {
            return;
        }

        // TODO Check fieldsets in the config of the module.
        $settingFieldsets = [
            // 'config' => Form\ConfigForm::class,
            'settings' => Form\SettingsFieldset::class,
            'site_settings' => Form\SiteSettingsFieldset::class,
            // 'user_settings' => Form\UserSettingsFieldset::class,
        ];
        if (!isset($settingFieldsets[$settingsType])) {
            return;
        }

        $settings = $services->get($settingsTypes[$settingsType]);
        $data = $this->prepareDataToPopulate($settings, $settingsType);
        if (empty($data)) {
            return;
        }

        $space = strtolower(__NAMESPACE__);

        $fieldset = $services->get('FormElementManager')->get($settingFieldsets[$settingsType]);
        $fieldset->setName($space);
        $form = $event->getTarget();
        $form->add($fieldset);
        $form->get($space)->populateValues($data);
    }

    /**
     * Prepare data for a form or a fieldset.
     *
     * To be overridden by module for specific keys.
     *
     * @todo Use form methods to populate.
     * @param SettingsInterface $settings
     * @param string $settingsType
     * @return array
     */
    protected function prepareDataToPopulate(SettingsInterface $settings, $settingsType)
    {
        $services = $this->getServiceLocator();
        $config = $services->get('Config');
        $space = strtolower(__NAMESPACE__);
        if (empty($config[$space][$settingsType])) {
            return;
        }

        $defaultSettings = $config[$space][$settingsType];

        $data = [];
        foreach ($defaultSettings as $name => $value) {
            $val = $settings->get($name, $value);
            $data[$name] = $val;
        }

        return $data;
    }

    /**
     * Check if a module is active.
     *
     * @param string $moduleClass
     * @return bool
     */
    protected function isModuleActive($moduleClass)
    {
        $services = $this->getServiceLocator();
        $moduleManager = $services->get('Omeka\ModuleManager');
        $module = $moduleManager->getModule($moduleClass);
        return $module
            && $module->getState() === \Omeka\Module\Manager::STATE_ACTIVE;
    }
}
