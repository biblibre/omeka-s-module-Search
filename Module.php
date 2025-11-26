<?php

/*
 * Copyright BibLibre, 2016-2017
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

use Laminas\ModuleManager\ModuleManager;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\EventManager\Event;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Module\AbstractModule;
use Composer\Semver\Comparator;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);

        $services = $this->getServiceLocator();
        $acl = $services->get('Omeka\Acl');
        $acl->allow(null, 'Search\Api\Adapter\SearchPageAdapter');
        $acl->allow(null, 'Search\Api\Adapter\SearchIndexAdapter');
        $acl->allow(null, 'Search\Api\Adapter\SavedQueryAdapter');
        $acl->allow(null, 'Search\Entity\SearchPage', 'read');
        $acl->allow(null, 'Search\Entity\SearchIndex', 'read');
        $acl->allow(null, 'Search\Entity\SavedQuery', 'create');
        $acl->allow(null, 'Search\Entity\SavedQuery', 'delete');
        $acl->allow(null, 'Search\Controller\Index');
        $acl->allow(null, 'Search\Controller\SavedQuery');

        $this->addRoutes();

        // Set the corresponding visibility rules on Search resources.
        $em = $services->get('Omeka\EntityManager');
        $filter = $em->getFilters()->getFilter('resource_visibility');
        $filter->addRelatedEntity('Search\Entity\SearchResource', 'resource_id');

        $this->dispatchSyncJobIfNeeded();
    }

    public function init(ModuleManager $moduleManager)
    {
        $event = $moduleManager->getEvent();
        $container = $event->getParam('ServiceManager');
        $serviceListener = $container->get('ServiceListener');

        $serviceListener->addServiceManager(
            'Search\AdapterManager',
            'search_adapters',
            'Search\Feature\AdapterProviderInterface',
            'getSearchAdapterConfig'
        );
        $serviceListener->addServiceManager(
            'Search\FormAdapterManager',
            'search_form_adapters',
            'Search\Feature\FormAdapterProviderInterface',
            'getSearchFormAdapterConfig'
        );
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        $sql = '
            CREATE TABLE IF NOT EXISTS `search_index` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `adapter` varchar(255) NOT NULL,
                `settings` text,
                `created` datetime NOT NULL,
                `modified` datetime DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ';
        $connection->exec($sql);
        $sql = '
            CREATE TABLE IF NOT EXISTS `search_page` (
                id INT AUTO_INCREMENT NOT NULL,
                `name` varchar(255) NOT NULL,
                `path` varchar(255) NOT NULL,
                `index_id` int(11) NOT NULL,
                `form_adapter` varchar(255) NOT NULL,
                `settings` text,
                `created` datetime NOT NULL,
                `modified` datetime DEFAULT NULL,
                PRIMARY KEY (id),
                FOREIGN KEY (`index_id`) REFERENCES `search_index` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ';
        $connection->exec($sql);

        $connection->exec('CREATE TABLE saved_query (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, site_id INT DEFAULT NULL, search_page_id INT DEFAULT NULL, query_string LONGTEXT NOT NULL, query_title VARCHAR(255) NOT NULL, query_description LONGTEXT DEFAULT NULL, INDEX IDX_496E6EF2A76ED395 (user_id), INDEX IDX_496E6EF2F6BD1646 (site_id), INDEX IDX_496E6EF281978C7E (search_page_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $connection->exec('ALTER TABLE saved_query ADD CONSTRAINT FK_496E6EF2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $connection->exec('ALTER TABLE saved_query ADD CONSTRAINT FK_496E6EF2F6BD1646 FOREIGN KEY (site_id) REFERENCES site (id) ON DELETE CASCADE');
        $connection->exec('ALTER TABLE saved_query ADD CONSTRAINT FK_496E6EF281978C7E FOREIGN KEY (search_page_id) REFERENCES search_page (id) ON DELETE CASCADE');

        $connection->executeStatement(<<<'SQL'
            CREATE TABLE search_resource (
                id INT AUTO_INCREMENT NOT NULL,
                index_id INT NOT NULL,
                resource_id INT NOT NULL,
                locked_by_pid INT DEFAULT NULL,
                indexed DATETIME DEFAULT NULL,
                touched DATETIME DEFAULT NULL,
                INDEX IDX_ACC65FA384337261 (index_id),
                INDEX IDX_ACC65FA389329D25 (resource_id),
                INDEX IDX_ACC65FA33E8304F0 (locked_by_pid),
                INDEX IDX_ACC65FA3D9416D95 (indexed),
                INDEX IDX_ACC65FA370D901A0 (touched),
                UNIQUE INDEX UNIQ_ACC65FA38433726189329D25 (index_id, resource_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $connection->executeStatement(<<<'SQL'
            ALTER TABLE search_resource
            ADD CONSTRAINT FK_ACC65FA384337261 FOREIGN KEY (index_id) REFERENCES search_index (id)
            ON DELETE CASCADE
        SQL);

        $connection->executeStatement(<<<'SQL'
            ALTER TABLE search_resource
            ADD CONSTRAINT FK_ACC65FA389329D25 FOREIGN KEY (resource_id) REFERENCES resource (id)
            ON DELETE CASCADE
        SQL);
    }

    public function upgrade($oldVersion, $newVersion,
        ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');

        if (version_compare($oldVersion, '0.1.1', '<')) {
            $connection->exec('
                ALTER TABLE search_page
                CHANGE `form` `form_adapter` varchar(255) NOT NULL
            ');
        }

        if (version_compare($oldVersion, '0.10.0', '<')) {
            $pages = $connection->fetchAll('SELECT id, settings FROM search_page WHERE form_adapter = ?', ['standard']);
            foreach ($pages as $page) {
                $settings = json_decode($page['settings'], true);
                $search_fields = [];
                if (isset($settings['form']['search_fields'])) {
                    foreach ($settings['form']['search_fields'] as $i => $fieldName) {
                        $search_fields[$fieldName] = [
                            'enabled' => '1',
                            'weight' => $i,
                        ];
                    }
                    $settings['form']['search_fields'] = $search_fields;
                    $connection->update('search_page', ['settings' => json_encode($settings)], ['id' => $page['id']]);
                }
            }
        }

        if (Comparator::lessThan($oldVersion, '0.11.0')) {
            $connection->exec('ALTER TABLE search_page MODIFY id INT AUTO_INCREMENT NOT NULL');

            $connection->exec('CREATE TABLE saved_query (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, site_id INT DEFAULT NULL, search_page_id INT DEFAULT NULL, query_string LONGTEXT NOT NULL, query_title VARCHAR(255) NOT NULL, query_description LONGTEXT DEFAULT NULL, INDEX IDX_496E6EF2A76ED395 (user_id), INDEX IDX_496E6EF2F6BD1646 (site_id), INDEX IDX_496E6EF281978C7E (search_page_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
            $connection->exec('ALTER TABLE saved_query ADD CONSTRAINT FK_496E6EF2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
            $connection->exec('ALTER TABLE saved_query ADD CONSTRAINT FK_496E6EF2F6BD1646 FOREIGN KEY (site_id) REFERENCES site (id) ON DELETE CASCADE');
            $connection->exec('ALTER TABLE saved_query ADD CONSTRAINT FK_496E6EF281978C7E FOREIGN KEY (search_page_id) REFERENCES search_page (id) ON DELETE CASCADE');
        }

        if (Comparator::lessThan($oldVersion, '0.14.0')) {
            $pages = $connection->executeQuery('SELECT id, settings, form_adapter FROM search_page')->fetchAll();
            foreach ($pages as $page) {
                $settings = json_decode($page['settings'], true);

                $enabled_facets = array_filter($settings['facets'] ?? [], fn ($a) => $a['enabled'] ?? false);
                uasort($enabled_facets, fn ($a, $b) => $a['weight'] - $b['weight']);
                $settings['facets'] = [];
                foreach ($enabled_facets as $fieldName => $facetData) {
                    $settings['facets'][] = [
                        'name' => $fieldName,
                        'label' => $facetData['display']['label'] ?? '',
                    ];
                }

                $enabled_sort_fields = array_filter($settings['sort_fields'] ?? [], fn ($a) => $a['enabled'] ?? false);
                uasort($enabled_sort_fields, fn ($a, $b) => $a['weight'] - $b['weight']);
                $settings['sort_fields'] = [];
                foreach ($enabled_sort_fields as $fieldName => $sortFieldData) {
                    $settings['sort_fields'][] = [
                        'name' => $fieldName,
                        'label' => $sortFieldData['display']['label'] ?? '',
                    ];
                }

                if ($page['form_adapter'] === 'standard') {
                    $enabled_search_fields = array_filter($settings['form']['search_fields'] ?? [], fn ($a) => $a['enabled'] ?? false);
                    uasort($enabled_search_fields, fn ($a, $b) => $a['weight'] - $b['weight']);
                    $settings['form'] ??= [];
                    $settings['form']['search_fields'] = [];
                    foreach ($enabled_search_fields as $fieldName => $searchFieldData) {
                        $settings['form']['search_fields'][] = [
                            'name' => $fieldName,
                        ];
                    }
                }

                $connection->update('search_page', ['settings' => json_encode($settings)], ['id' => $page['id']]);
            }
        }

        if (Comparator::lessThan($oldVersion, '0.18.0')) {
            $constraint_name = $connection->fetchOne(<<<'SQL'
                SELECT constraint_name FROM information_schema.key_column_usage
                WHERE table_schema = database()
                    AND table_name = 'search_page'
                    AND column_name = 'index_id'
                    AND referenced_table_name = 'search_index'
            SQL);
            if ($constraint_name) {
                $connection->executeStatement(<<<SQL
                    ALTER TABLE search_page DROP CONSTRAINT $constraint_name
                SQL);
            }

            $connection->executeStatement(<<<'SQL'
                ALTER TABLE search_index MODIFY COLUMN id INT AUTO_INCREMENT NOT NULL
            SQL);
            $connection->executeStatement(<<<'SQL'
                ALTER TABLE search_page MODIFY COLUMN index_id INT NOT NULL
            SQL);
            $connection->executeStatement(<<<'SQL'
                ALTER TABLE search_page ADD CONSTRAINT FK_4F10A34984337261 FOREIGN KEY (index_id) REFERENCES search_index (id)
            SQL);

            $connection->executeStatement(<<<'SQL'
                CREATE TABLE search_resource (
                    id INT AUTO_INCREMENT NOT NULL,
                    index_id INT NOT NULL,
                    resource_id INT NOT NULL,
                    locked_by_pid INT DEFAULT NULL,
                    indexed DATETIME DEFAULT NULL,
                    touched DATETIME DEFAULT NULL,
                    INDEX IDX_ACC65FA384337261 (index_id),
                    INDEX IDX_ACC65FA389329D25 (resource_id),
                    INDEX IDX_ACC65FA33E8304F0 (locked_by_pid),
                    INDEX IDX_ACC65FA3D9416D95 (indexed),
                    INDEX IDX_ACC65FA370D901A0 (touched),
                    UNIQUE INDEX UNIQ_ACC65FA38433726189329D25 (index_id, resource_id),
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);

            $connection->executeStatement(<<<'SQL'
                ALTER TABLE search_resource
                ADD CONSTRAINT FK_ACC65FA384337261 FOREIGN KEY (index_id) REFERENCES search_index (id)
                ON DELETE CASCADE
            SQL);

            $connection->executeStatement(<<<'SQL'
                ALTER TABLE search_resource
                ADD CONSTRAINT FK_ACC65FA389329D25 FOREIGN KEY (resource_id) REFERENCES resource (id)
                ON DELETE CASCADE
            SQL);

            $connection->executeStatement(<<<'SQL'
                INSERT INTO search_resource (index_id, resource_id, indexed, touched)
                SELECT
                    search_index.id,
                    resource.id,
                    COALESCE(resource.modified, resource.created),
                    COALESCE(resource.modified, resource.created)
                FROM resource JOIN search_index
                ORDER BY search_index.id, resource.id
                ON DUPLICATE KEY UPDATE indexed = VALUES(indexed), touched = VALUES(touched)
            SQL);

            // Enable periodic check so that indexation continues to work after
            // upgrade without having to run bin/sync
            $settings = $serviceLocator->get('Omeka\Settings');
            $settings->set('search_check_interval', 60);
        }
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');

        $connection->exec('DROP TABLE IF EXISTS saved_query');
        $connection->exec('DROP TABLE IF EXISTS search_resource');
        $connection->exec('DROP TABLE IF EXISTS search_page');
        $connection->exec('DROP TABLE IF EXISTS search_index');
    }

    public function getConfigForm(PhpRenderer $renderer)
    {
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');
        $formElementManager = $services->get('FormElementManager');
        $form = $formElementManager->get(Form\ConfigForm::class);

        $form->populateValues([
            'search_check_interval' => $settings->get('search_check_interval', ''),
        ]);

        return $renderer->formCollection($form, false);
    }

    public function handleConfigForm(AbstractController $controller)
    {
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');
        $formElementManager = $services->get('FormElementManager');
        $form = $formElementManager->get(Form\ConfigForm::class);

        $form->setData($controller->params()->fromPost());
        if (!$form->isValid()) {
            return false;
        }

        $data = $form->getData();

        $settings->set('search_check_interval', (int) $data['search_check_interval']);

        return true;
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $identifiers = ['Omeka\Api\Adapter\ItemAdapter', 'Omeka\Api\Adapter\ItemSetAdapter'];
        foreach ($identifiers as $identifier) {
            $sharedEventManager->attach(
                $identifier,
                'api.update.post',
                [$this, 'onResourceUpdatePost']
            );
            $sharedEventManager->attach(
                $identifier,
                'api.create.post',
                [$this, 'onResourceCreatePost']
            );
            $sharedEventManager->attach(
                $identifier,
                'api.delete.post',
                [$this, 'onResourceDeletePost']
            );
        }

        $identifiers = ['Omeka\Controller\Admin\Item', 'Omeka\Controller\Admin\ItemSet'];
        foreach ($identifiers as $identifier) {
            $sharedEventManager->attach(
                $identifier,
                'view.details',
                [$this, 'onResourceViewDetails']
            );
            $sharedEventManager->attach(
                $identifier,
                'view.show.sidebar',
                [$this, 'onResourceViewShowSidebar']
            );
        }

        $sharedEventManager->attach(
            'Search\Api\Adapter\SearchIndexAdapter',
            'api.create.post',
            [$this, 'onSearchIndexCreatePost']
        );
        $sharedEventManager->attach(
            'Search\Api\Adapter\SearchIndexAdapter',
            'api.update.post',
            [$this, 'onSearchIndexUpdatePost']
        );
    }

    public function onResourceUpdatePost(Event $event)
    {
        $response = $event->getParam('response');
        $resource = $response->getContent();
        $this->touchResource($resource);
    }

    public function onResourceCreatePost(Event $event)
    {
        $response = $event->getParam('response');
        $resource = $response->getContent();
        $this->touchResource($resource);
    }

    protected function touchResource(\Omeka\Entity\Resource $resource)
    {
        $services = $this->getServiceLocator();
        $indexationService = $services->get('Search\IndexationService');
        $connection = $services->get('Omeka\Connection');

        $searchIndexes = $connection->fetchAll('SELECT id, settings FROM search_index');

        $now = new \DateTime();
        foreach ($searchIndexes as $searchIndex) {
            $settings_json = $searchIndex['settings'] ?? '{}';
            $settings = json_decode($settings_json, true);
            $resources = $settings['resources'] ?? [];
            if (in_array($resource->getResourceName(), $resources)) {
                $indexationService->touchResource($searchIndex['id'], $resource->getId(), $now);
            }
        }
    }

    public function onResourceDeletePost(Event $event)
    {
        $serviceLocator = $this->getServiceLocator();
        $api = $serviceLocator->get('Omeka\ApiManager');
        $logger = $serviceLocator->get('Omeka\Logger');
        $request = $event->getParam('request');

        $searchIndexes = $api->search('search_indexes')->getContent();
        foreach ($searchIndexes as $searchIndex) {
            $searchIndexSettings = $searchIndex->settings();
            if (!in_array($request->getResource(), $searchIndexSettings['resources'])) {
                continue;
            }

            try {
                $indexer = $searchIndex->indexer();
                $indexer->deleteResource($request->getResource(), $request->getId());
            } catch (\Exception $e) {
                $logger->err(sprintf('Search: failed to delete resource: %s', $e));
            }
        }
    }

    public function onResourceViewDetails(Event $event)
    {
        /** @var \Laminas\View\Renderer\PhpRenderer */
        $renderer = $event->getTarget();
        $entity = $event->getParam('entity');
        $resourceId = $entity->id();

        $services = $this->getServiceLocator();
        $em = $services->get('Omeka\EntityManager');
        $searchResources = $em->getRepository(Entity\SearchResource::class)->findBy(['resource' => $resourceId]);

        echo $renderer->partial('search/common/resource-details', ['resource' => $entity, 'searchResources' => $searchResources]);
    }

    public function onResourceViewShowSidebar(Event $event)
    {
        /** @var \Laminas\View\Renderer\PhpRenderer */
        $renderer = $event->getTarget();
        $resource = $renderer->get('resource');
        $resourceId = $resource->id();

        $services = $this->getServiceLocator();
        $em = $services->get('Omeka\EntityManager');
        $searchResources = $em->getRepository(Entity\SearchResource::class)->findBy(['resource' => $resourceId]);

        echo $renderer->partial('search/common/resource-details', ['resource' => $resource, 'searchResources' => $searchResources]);
    }

    public function onSearchIndexCreatePost(Event $event)
    {
        $indexationService = $this->getServiceLocator()->get('Search\IndexationService');
        $searchIndex = $event->getParam('response')->getContent();

        $indexationService->refreshIndexResources($searchIndex->getId());
    }

    public function onSearchIndexUpdatePost(Event $event)
    {
        $indexationService = $this->getServiceLocator()->get('Search\IndexationService');
        $searchIndex = $event->getParam('response')->getContent();

        $indexationService->refreshIndexResources($searchIndex->getId());
    }

    protected function addRoutes()
    {
        $serviceLocator = $this->getServiceLocator();
        $settings = $serviceLocator->get('Omeka\Settings');
        $router = $serviceLocator->get('Router');
        $api = $serviceLocator->get('Omeka\ApiManager');

        if (!$router instanceof \Laminas\Router\Http\TreeRouteStack) {
            return;
        }

        $pages = $api->search('search_pages')->getContent();
        foreach ($pages as $page) {
            $path = $page->path();
            $router->addRoute('search-page-' . $page->id(), [
                'type' => 'segment',
                'options' => [
                    'route' => '/s/:site-slug/' . $path,
                    'defaults' => [
                        '__NAMESPACE__' => 'Search\Controller',
                        '__SITE__' => true,
                        '__SEARCH_PAGE__' => true,
                        'controller' => 'Index',
                        'action' => 'search',
                        'id' => $page->id(),
                    ],
                ],
            ]);
        }
    }

    protected function dispatchSyncJobIfNeeded()
    {
        $services = $this->getServiceLocator();
        $acl = $services->get('Omeka\Acl');

        if ($acl->userIsAllowed('Omeka\Entity\Resource', 'view-all')) {
            $settings = $services->get('Omeka\Settings');
            $check_interval = (int) $settings->get('search_check_interval', 0);
            if ($check_interval) {
                $check_latest = (int) $settings->get('search_check_latest', 0);
                $now = time();
                if (!$check_latest || $check_latest + $check_interval < $now) {
                    $settings->set('search_check_latest', $now);

                    $connection = $services->get('Omeka\Connection');
                    $indexation_needed = $connection->fetchOne(<<<'SQL'
                        SELECT 1 FROM search_resource
                        WHERE locked_by_pid IS NULL AND (indexed IS NULL or indexed < touched)
                        LIMIT 1
                    SQL);

                    if ($indexation_needed) {
                        $jobDispatcher = $services->get('Omeka\Job\Dispatcher');
                        $jobDispatcher->dispatch(Job\Sync::class, ['max_execution_time' => $check_interval]);
                    }
                }
            }
        }
    }
}
