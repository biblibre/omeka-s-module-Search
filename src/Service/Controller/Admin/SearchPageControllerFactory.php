<?php
namespace Search\Service\Controller\Admin;

use Search\Controller\Admin\SearchPageController;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SearchPageControllerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $controllers)
    {
        $services = $controllers->getServiceLocator();
        $entityManager = $services->get('Omeka\EntityManager');
        $searchAdapterManager = $services->get('Search\AdapterManager');
        $searchFormAdapterManager = $services->get('Search\FormAdapterManager');

        $controller = new SearchPageController;
        $controller->setEntityManager($entityManager);
        $controller->setSearchAdapterManager($searchAdapterManager);
        $controller->setSearchFormAdapterManager($searchFormAdapterManager);

        return $controller;
    }
}
