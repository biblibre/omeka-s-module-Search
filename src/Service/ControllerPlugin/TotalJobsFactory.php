<?php
namespace Search\Service\ControllerPlugin;

use Interop\Container\ContainerInterface;
use Search\Mvc\Controller\Plugin\TotalJobs;
use Zend\ServiceManager\Factory\FactoryInterface;

class TotalJobsFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new TotalJobs(
            $services->get('Omeka\EntityManager')
        );
    }
}
