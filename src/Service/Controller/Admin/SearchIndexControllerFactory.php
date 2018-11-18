<?php
namespace Search\Service\Controller\Admin;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Search\Controller\Admin\SearchIndexController;

class SearchIndexControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedNamed, array $options = null)
    {
        return new SearchIndexController(
            $services->get('Omeka\EntityManager'),
            $services->get('Search\AdapterManager')
        );
    }
}
