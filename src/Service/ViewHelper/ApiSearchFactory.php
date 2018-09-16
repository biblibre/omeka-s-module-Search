<?php
namespace Search\Service\ViewHelper;

use Interop\Container\ContainerInterface;
use Search\View\Helper\ApiSearch;
use Zend\ServiceManager\Factory\FactoryInterface;

class ApiSearchFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ApiSearch(
            $services->get('ControllerPluginManager')->get('apiSearch')
        );
    }
}
