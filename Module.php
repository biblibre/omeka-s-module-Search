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
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceLocatorInterface;
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

        $acl = $this->getServiceLocator()->get('Omeka\Acl');
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
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
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
                `index_id` int(11) unsigned NOT NULL,
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
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');

        $connection->exec('DROP TABLE IF EXISTS saved_query');
        $connection->exec('DROP TABLE IF EXISTS search_page');
        $connection->exec('DROP TABLE IF EXISTS search_index');
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.create.post',
            [$this, 'updateSearchIndex']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.update.post',
            [$this, 'updateSearchIndex']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.delete.post',
            [$this, 'updateSearchIndex']
        );

        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemSetAdapter',
            'api.create.post',
            [$this, 'updateSearchIndex']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemSetAdapter',
            'api.update.post',
            [$this, 'updateSearchIndex']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemSetAdapter',
            'api.delete.post',
            [$this, 'updateSearchIndex']
        );
    }

    public function updateSearchIndex($event)
    {
        $serviceLocator = $this->getServiceLocator();
        $api = $serviceLocator->get('Omeka\ApiManager');
        $logger = $serviceLocator->get('Omeka\Logger');

        $request = $event->getParam('request');
        $response = $event->getParam('response');
        $requestResource = $request->getResource();

        $searchIndexes = $api->search('search_indexes')->getContent();
        foreach ($searchIndexes as $searchIndex) {
            $searchIndexSettings = $searchIndex->settings();
            if (in_array($requestResource, $searchIndexSettings['resources'])) {
                $indexer = $searchIndex->indexer();

                if ($request->getOperation() == 'delete') {
                    $id = $request->getId();
                    try {
                        $indexer->deleteResource($requestResource, $id);
                    } catch (\Exception $e) {
                        $logger->err(sprintf('Search: failed to delete resource: %s', $e));
                    }
                } else {
                    $resource = $response->getContent();
                    try {
                        $indexer->indexResource($resource);
                    } catch (\Exception $e) {
                        $logger->err(sprintf('Search: failed to index resource: %s', $e));
                    }
                }
            }
        }
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
                        'controller' => 'Index',
                        'action' => 'search',
                        'id' => $page->id(),
                    ],
                ],
            ]);
        }
    }
}
