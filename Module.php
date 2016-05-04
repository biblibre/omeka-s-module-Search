<?php

/*
 * Copyright BibLibre, 2016
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

use Zend\Mvc\Controller\AbstractController;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorInterface;
use Omeka\Module\AbstractModule;

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
        $acl->allow(null, 'Search\Entity\SearchPage', 'read');
        $acl->allow(null, 'Search\Entity\SearchIndex', 'read');
        $acl->allow(null, 'Search\Controller\Index');

        $this->addRoutes();
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
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `path` varchar(255) NOT NULL,
                `index_id` int(11) unsigned NOT NULL,
                `form` varchar(255) NOT NULL,
                `created` datetime NOT NULL,
                `modified` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`index_id`) REFERENCES `search_index` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ';
        $connection->exec($sql);
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        $sql = 'DROP TABLE IF EXISTS `search_page`';
        $connection->exec($sql);
        $sql = 'DROP TABLE IF EXISTS `search_index`';
        $connection->exec($sql);
    }

    protected function addRoutes()
    {
        $serviceLocator = $this->getServiceLocator();
        $settings = $serviceLocator->get('Omeka\Settings');
        $router = $serviceLocator->get('Router');
        $api = $serviceLocator->get('Omeka\ApiManager');

        if (!$router instanceof \Zend\Mvc\Router\Http\TreeRouteStack) {
            return;
        }

        $pages = $api->search('search_pages')->getContent();
        foreach ($pages as $page) {
            $path = $page->path();
            $router->addRoute('search-' . $path, [
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
