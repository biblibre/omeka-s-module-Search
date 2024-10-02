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
        $translator = $services->get('MvcTranslator');

        $itemSetSelect = new ItemSetSelect();
        $itemSetSelect->setApiManager($api);
        $itemSetSelect->setTranslator($translator);

        return $itemSetSelect;
    }
}
