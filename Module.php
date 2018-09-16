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
use Omeka\Stdlib\Message;
use Search\Form\ConfigForm;
use Search\Indexer\AbstractIndexer;
use Zend\EventManager\Event;
use Zend\EventManager\EventInterface;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Form\Element;
use Zend\Form\Fieldset;
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
        $event = $moduleManager->getEvent();
        $container = $event->getParam('ServiceManager');
        $serviceListener = $container->get('ServiceListener');

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
        $messenger = new Messenger;
        $optionalModule = 'jQueryUI';
        if (!$this->isModuleEnabled($optionalModule, $serviceLocator)) {
            $messenger->addWarning('The module jQueryUI is required to customize the search pages.'); // @translate
        }
        $optionalModule = 'Reference';
        if (!$this->isModuleEnabled($optionalModule, $serviceLocator)) {
            $messenger->addWarning('The module Reference is required to use the facets with the default internal adapter.'); // @translate
        }

        $sql = <<<'SQL'
CREATE TABLE search_index (
    id INT AUTO_INCREMENT NOT NULL,
    name VARCHAR(255) NOT NULL,
    adapter VARCHAR(255) NOT NULL,
    settings LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json_array)',
    created DATETIME NOT NULL,
    modified DATETIME DEFAULT NULL,
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
CREATE TABLE search_page (
    id INT AUTO_INCREMENT NOT NULL,
    index_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    path VARCHAR(255) NOT NULL,
    form_adapter VARCHAR(255) NOT NULL,
    settings LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json_array)',
    created DATETIME NOT NULL,
    modified DATETIME DEFAULT NULL,
    INDEX IDX_4F10A34984337261 (index_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
ALTER TABLE search_page ADD CONSTRAINT FK_4F10A34984337261 FOREIGN KEY (index_id) REFERENCES search_index (id) ON DELETE CASCADE;
SQL;
        $connection = $serviceLocator->get('Omeka\Connection');
        $sqls = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($sqls as $sql) {
            $connection->exec($sql);
        }

        $settings = $serviceLocator->get('Omeka\Settings');
        $this->manageSettings($settings, 'install', 'config');
        $this->manageSettings($settings, 'install', 'settings');
        $this->manageSiteSettings($serviceLocator, 'install');

        // Create the internal adapter.
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
        $sql = <<<'SQL'
DROP TABLE IF EXISTS `search_page`;
DROP TABLE IF EXISTS `search_index`;
SQL;
        $connection = $serviceLocator->get('Omeka\Connection');
        $sqls = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($sqls as $sql) {
            $connection->exec($sql);
        }

        $settings = $serviceLocator->get('Omeka\Settings');
        $this->manageSettings($settings, 'uninstall', 'config');
        $this->manageSettings($settings, 'uninstall', 'settings');
    }

    public function upgrade($oldVersion, $newVersion,
        ServiceLocatorInterface $serviceLocator)
    {
        require_once 'data/scripts/upgrade.php';
    }

    protected function manageSettings($settings, $process, $key = 'config')
    {
        $config = require __DIR__ . '/config/module.config.php';
        $defaultSettings = $config[strtolower(__NAMESPACE__)][$key];
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

    protected function manageSiteSettings(ServiceLocatorInterface $serviceLocator, $process)
    {
        $siteSettings = $serviceLocator->get('Omeka\Settings\Site');
        $api = $serviceLocator->get('Omeka\ApiManager');
        $sites = $api->search('sites')->getContent();
        foreach ($sites as $site) {
            $siteSettings->setTargetId($site->id());
            $this->manageSettings($siteSettings, $process, 'site_settings');
        }
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach(
            // Hacked, because the admin layout doesn't use a partial or a trigger for the search engine.
            '*',
            'view.layout',
            function (EventInterface $event) {
                $view = $event->getTarget();
                // TODO How to attach all admin events only?
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
            [$this, 'addSettingFormElements']
        );
        $sharedEventManager->attach(
            \Omeka\Form\SettingForm::class,
            'form.add_input_filters',
            [$this, 'addSettingsFormFilters']
        );
        $sharedEventManager->attach(
            \Omeka\Form\SiteSettingsForm::class,
            'form.add_elements',
            [$this, 'addFormElementsSiteSettings']
        );
        $sharedEventManager->attach(
            \Omeka\Form\SiteSettingsForm::class,
            'form.add_input_filters',
            [$this, 'addSettingsFormFilters']
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
        $html = $renderer->formCollection($form);
        return $html;
    }

    public function handleConfigForm(AbstractController $controller)
    {
        $services = $this->getServiceLocator();
        $config = $services->get('Config');
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
        $defaultSettings = $config[strtolower(__NAMESPACE__)]['config'];
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
     * @param AbstractIndexer $indexer
     * @param string $resourceName
     * @param int $id
     */
    protected function deleteIndexResource(AbstractIndexer $indexer, $resourceName, $id)
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
     * @param AbstractIndexer $indexer
     * @param Resource $resource
     */
    protected function updateIndexResource(AbstractIndexer $indexer, Resource $resource)
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

    public function addSettingFormElements(Event $event)
    {
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');
        $this->addSettingsFormElements($event, $settings, true);
    }

    public function addFormElementsSiteSettings(Event $event)
    {
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings\Site');
        $this->addSettingsFormElements($event, $settings, false);
    }

    protected function addSettingsFormElements(Event $event, $settings, $isAdmin)
    {
        $form = $event->getTarget();

        $services = $this->getServiceLocator();
        $config = $services->get('Config');
        $defaultSettings = $isAdmin
            ? $config[strtolower(__NAMESPACE__)]['settings']
            : $config[strtolower(__NAMESPACE__)]['site_settings'];

        $fieldset = new Fieldset('search');
        $fieldset->setLabel('Search'); // @translate

        $api = $services->get('Omeka\ApiManager');

        $pages = $api->search('search_pages')->getContent();
        $valueOptions = [];
        foreach ($pages as $page) {
            $valueOptions[$page->id()] = sprintf('%s (/%s)', $page->name(), $page->path());
        }
        $pagesOptions = $valueOptions;

        // For admin, the main page is a path.
        if ($isAdmin) {
            $valueOptions = [];
            $basePath = $services->get('ViewHelperManager')->get('BasePath');
            $adminBasePath = $basePath('admin/');
            foreach ($pages as $page) {
                $valueOptions[$adminBasePath . $page->path()] = sprintf('%s (/%s)', $page->name(), $page->path());
            }
        }
        $fieldset->add([
            'name' => 'search_main_page',
            'type' => Element\Select::class,
            'options' => [
                'label' => 'Default search page', // @translate
                'info' => $isAdmin
                    ? 'This search engine is used in the admin bar.' // @translate
                    : '',
                'value_options' => $valueOptions,
                'empty_option' => $isAdmin
                    ? 'Select the search engine for the admin bar…' // @translate
                    : 'Select the default search engine for the site…', // @translate
            ],
            'attributes' => [
                'value' => $settings->get(
                    'search_main_page',
                    $defaultSettings['search_main_page']
                ),
            ],
        ]);

        $fieldset->add([
            'name' => 'search_pages',
            'type' => Element\MultiCheckbox::class,
            'options' => [
                'label' => 'Available search pages', // @translate
                'value_options' => $pagesOptions,
            ],
            'attributes' => [
                'value' => $settings->get(
                    'search_pages',
                    $defaultSettings['search_pages']
                ),
            ],
        ]);

        if ($isAdmin) {
            $indexes = $api->search('search_indexes')->getContent();
            $valueOptions = [];
            foreach ($indexes as $index) {
                $valueOptions[$index->id()] = sprintf('%s (%s)', $index->name(), $index->adapterLabel());
            }
            $fieldset->add([
                'name' => 'search_api_page',
                'type' => Element\Select::class,
                'options' => [
                    'label' => 'Page used for quick api search', // @translate
                    'info' => 'The method apiSearch() allows to do a quick search in some cases. It requires a mapping done with the Omeka api and the selected index.', // @translate
                    'value_options' => $pagesOptions,
                    'empty_option' => 'Select the page for apiSearch()…', // @translate
                ],
                'attributes' => [
                    'value' => $settings->get(
                        'search_api_page',
                        $defaultSettings['search_api_page']
                    ),
                ],
            ]);
        }

        $form->add($fieldset);
    }

    public function addSettingsFormFilters(Event $event)
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

    /**
     * Check if a module is enabled.
     *
     * @param string $module
     * @param ServiceLocatorInterface $serviceLocator
     * @return bool
     */
    protected function isModuleEnabled($module, ServiceLocatorInterface $serviceLocator)
    {
        $moduleManager = $serviceLocator->get('Omeka\ModuleManager');
        $module = $moduleManager->getModule($module);
        return $module
            && $module->getState() === \Omeka\Module\Manager::STATE_ACTIVE;
    }
}
