<?php

namespace Search\Service;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Omeka\Service\Exception\ConfigException;
use Search\FormAdapter\Manager;

class FormAdapterManagerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $config = $serviceLocator->get('Config');
        if (!isset($config['search_form_adapters'])) {
            throw new ConfigException('Missing search form adapters configuration');
        }

        return new Manager($serviceLocator, $config['search_form_adapters']);
    }
}
