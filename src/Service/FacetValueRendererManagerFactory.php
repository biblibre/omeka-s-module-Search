<?php
namespace Search\Service;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Omeka\Service\Exception\ConfigException;
use Search\FacetValueRenderer\Manager;

class FacetValueRendererManagerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $config = $serviceLocator->get('Config');
        if (!isset($config['search_facet_value_renderers'])) {
            throw new ConfigException('Missing search facet value renderers configuration');
        }
        return new Manager($serviceLocator, $config['search_facet_value_renderers']);
    }
}
