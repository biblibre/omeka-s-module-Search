<?php

namespace Search\Service\FormElement;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Search\FormElement\HasMedia;

class HasMediaFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedNamed, array $options = null)
    {
        $translator = $services->get('MvcTranslator');

        $hasMedia = new HasMedia;
        $hasMedia->setTranslator($translator);

        return $hasMedia;
    }
}
