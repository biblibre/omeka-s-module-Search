<?php
namespace Search\Service\Controller\Admin;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Search\Controller\Admin\SearchIndexController;

class SearchIndexControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedNamed, array $options = null)
    {
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
