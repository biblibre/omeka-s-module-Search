<?php
namespace Search\Service;

use Interop\Container\ContainerInterface;
use Search\Api\ManagerDelegator;
use Zend\ServiceManager\Factory\DelegatorFactoryInterface;

class ApiManagerDelegatorFactory implements DelegatorFactoryInterface
{
    /**
     * Create the Api Manager service (delegator).
     *
     * @return ManagerDelegator
     */
    public function __invoke(ContainerInterface $serviceLocator, $name, callable $callback, array $options = null)
    {
        $adapterManager = $serviceLocator->get('Omeka\ApiAdapterManager');
        $acl = $serviceLocator->get('Omeka\Acl');
        $logger = $serviceLocator->get('Omeka\Logger');
        $translator = $serviceLocator->get('MvcTranslator');
        $manager = new ManagerDelegator($adapterManager, $acl, $logger, $translator);
        $apiSearch = $serviceLocator->get('ControllerPluginManager')->get('apiSearch');
        $manager->setApiSearch($apiSearch);
        return $manager;
    }
}
