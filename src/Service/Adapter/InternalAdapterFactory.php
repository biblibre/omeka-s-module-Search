<?php
namespace Search\Service\Adapter;

use Interop\Container\ContainerInterface;
use Search\Adapter\InternalAdapter;
use Zend\ServiceManager\Factory\FactoryInterface;

class InternalAdapterFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $api = $services->get('Omeka\ApiManager');
        $translator = $services->get('MvcTranslator');
        $adapter = new InternalAdapter($api, $translator);
        return $adapter;
    }
}
