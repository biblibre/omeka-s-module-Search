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
        $translator = $services->get('MvcTranslator');

        $resourceClassSelect = new ResourceClassSelect();
        $resourceClassSelect->setApiManager($api);
        $resourceClassSelect->setTranslator($translator);

        return $resourceClassSelect;
    }
}
