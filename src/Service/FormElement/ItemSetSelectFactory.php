<?php

namespace Search\Service\FormElement;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Search\FormElement\ItemSetSelect;

class ItemSetSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedNamed, array $options = null)
    {
        $api = $services->get('Omeka\ApiManager');

        $itemSetSelect = new ItemSetSelect(null, $options ?? []);
        $itemSetSelect->setApiManager($api);

        return $itemSetSelect;
    }
}
