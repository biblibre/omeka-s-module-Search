<?php
namespace Search\Service\Controller\Admin;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Search\Controller\Admin\SearchPageController;

class SearchPageControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
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
