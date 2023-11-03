<?php

namespace Search\Service\FormElement;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Search\FormElement\ResourceClassSelect;

class ResourceClassSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedNamed, array $options = null)
    {
        $api = $services->get('Omeka\ApiManager');

        $resourceClassSelect = new ResourceClassSelect(null, $options ?? []);
        $resourceClassSelect->setApiManager($api);

        return $resourceClassSelect;
    }
}
