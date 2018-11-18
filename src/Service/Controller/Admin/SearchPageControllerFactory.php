<?php
namespace Search\Service\Controller\Admin;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Search\Controller\Admin\SearchPageController;

class SearchPageControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new SearchPageController(
            $services->get('Omeka\EntityManager'),
            $services->get('Search\AdapterManager'),
            $services->get('Search\FormAdapterManager')
        );
    }
}
