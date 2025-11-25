<?php

namespace Search\Service;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Search\IndexationService;

class IndexationServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $connection = $services->get('Omeka\Connection');

        $service = new IndexationService($connection);

        return $service;
    }
}
