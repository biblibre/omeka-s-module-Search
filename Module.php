<?php

/*
 * Copyright BibLibre, 2016-2017
 * Copyright Daniel Berthereau, 2017-2019
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

if (!class_exists(\Generic\AbstractModule::class)) {
    require file_exists(dirname(__DIR__) . '/Generic/AbstractModule.php')
        ? dirname(__DIR__) . '/Generic/AbstractModule.php'
        : __DIR__ . '/src/Generic/AbstractModule.php';
}

use Generic\AbstractModule;
use Omeka\Entity\Resource;
use Omeka\Mvc\Controller\Plugin\Messenger;
use Omeka\Stdlib\Message;
use Search\Indexer\IndexerInterface;
use Zend\EventManager\Event;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\ModuleManager\ModuleManager;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorInterface;

class Module extends AbstractModule
{
    const NAMESPACE = __NAMESPACE__;

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

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);
        $this->addAclRules();
        $this->addRoutes();
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        parent::install($serviceLocator);

        $messenger = new Messenger;
        $optionalModule = 'jQueryUI';
        if (!$this->isModuleActive($optionalModule)) {
            $messenger->addWarning('The module jQueryUI is required to customize the search pages.'); // @translate
        }
        $optionalModule = 'Reference';
        if (!$this->isModuleActive($optionalModule)) {
            $messenger->addWarning('The module Reference is required to use the facets with the default internal adapter.'); // @translate
        }

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

    protected function addAclRules()
    {
        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        $acl
            // This first rule duplicates the second, but is needed for a site.
            ->allow(
                null,
                [
                    \Search\Controller\IndexController::class,
                    \Search\Api\Adapter\SearchPageAdapter::class,
                    \Search\Api\Adapter\SearchIndexAdapter::class,
                ],
                ['read', 'search']
            )
            ->allow(
                null,
                [
                    \Search\Controller\IndexController::class,
                    \Search\Api\Adapter\SearchPageAdapter::class,
                    \Search\Api\Adapter\SearchIndexAdapter::class,
                ]
            )
            ->allow(
                null,
                [
                    \Search\Entity\SearchPage::class,
                    \Search\Entity\SearchIndex::class,
                ],
                ['read']
            );
    }

    protected function addRoutes()
    {
        $services = $this->getServiceLocator();

        /** @var \Omeka\Mvc\Status $status */
        $status = $services->get('Omeka\Status');
        if ($status->isApiRequest()) {
            return;
        }

        $router = $services->get('Router');
        if (!$router instanceof \Zend\Router\Http\TreeRouteStack) {
            return;
        }

        $api = $services->get('Omeka\ApiManager');
        $pages = $api->search('search_pages')->getContent();

        $isOldOmeka = version_compare(\Omeka\Module::VERSION, '1.3.0', '<');
        $isAdminRequest = $isOldOmeka
            ? strpos($_SERVER['REQUEST_URI'], '/admin/') !== false
            : $status->isAdminRequest();
        if ($isAdminRequest) {
            $settings = $services->get('Omeka\Settings');
            $adminSearchPages = $settings->get('search_pages', []);
            foreach ($pages as $page) {
                $pageId = $page->id();
                if (in_array($pageId, $adminSearchPages)) {
                    $router->addRoute(
                        'search-admin-page-' . $pageId,
                        [
                            'type' => \Zend\Router\Http\Segment::class,
                            'options' => [
                                'route' => '/admin/' . $page->path(),
                                'defaults' => [
                                    '__NAMESPACE__' => 'Search\Controller',
                                    '__ADMIN__' => true,
                                    'controller' => \Search\Controller\IndexController::class,
                                    'action' => 'search',
                                    'id' => $pageId,
                                ],
                            ],
                        ]
                    );
                }
            }
        }

        // Public search pages are required to manage them at site level.
        foreach ($pages as $page) {
            $pageId = $page->id();
            $router->addRoute(
                'search-page-' . $pageId,
                [
                    'type' => \Zend\Router\Http\Segment::class,
                    'options' => [
                        'route' => '/s/:site-slug/' . $page->path(),
                        'defaults' => [
                            '__NAMESPACE__' => 'Search\Controller',
                            '__SITE__' => true,
                            'controller' => \Search\Controller\IndexController::class,
                            'action' => 'search',
                            'id' => $pageId,
                        ],
                    ],
                ]
            );
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

    public function handleSiteSettings(Event $event)
    {
        // This is an exception, because there is already a fieldset named
        // "search" in the core, so it should be named "search_module".

        $services = $this->getServiceLocator();
        $settingsType = 'site_settings';
        $settings = $services->get('Omeka\Settings\Site');

        $site = $services->get('ControllerPluginManager')->get('currentSite');
        $id = $site()->id();

        $this->initDataToPopulate($settings, $settingsType, $id);

        $data = $this->prepareDataToPopulate($settings, $settingsType);
        if (is_null($data)) {
            return;
        }

        $space = 'search_module';

        $fieldset = $services->get('FormElementManager')->get(\Search\Form\SiteSettingsFieldset::class);
        $fieldset->setName($space);
        $form = $event->getTarget();
        $form->add($fieldset);
        $form->get($space)->populateValues($data);
    }

    public function handleMainSettingsFilters(Event $event)
    {
        $inputFilter = $event->getParam('inputFilter');
        $inputFilter->get('search_module')
            ->add([
                'name' => 'search_pages',
                'required' => false,
            ])
            ->add([
                'name' => 'search_main_page',
                'required' => false,
            ])
            ->add([
                'name' => 'search_api_page',
                'required' => false,
            ]);
    }

    public function handleSiteSettingsFilters(Event $event)
    {
        $inputFilter = $event->getParam('inputFilter');
        $inputFilter->get('search_module')
            ->add([
                'name' => 'search_pages',
                'required' => false,
            ])
            ->add([
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
}
