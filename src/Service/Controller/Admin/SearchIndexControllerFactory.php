<?php
namespace Search\Service\Controller\Admin;

use Search\Controller\Admin\SearchIndexController;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SearchIndexControllerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $controllers)
    {
        $services = $controllers->getServiceLocator();
        $entityManager = $services->get('Omeka\EntityManager');
        $searchAdapterManager = $services->get('Search\AdapterManager');
        $jobDispatcher = $services->get('Omeka\JobDispatcher');

        $controller = new SearchIndexController;
        $controller->setEntityManager($entityManager);
        $controller->setSearchAdapterManager($searchAdapterManager);
        $controller->setJobDispatcher($jobDispatcher);

        return $controller;
    }
}
