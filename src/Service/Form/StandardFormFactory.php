<?php
namespace Search\Service\Form;

use Interop\Container\ContainerInterface;
use Search\Form\StandardForm;
use Laminas\ServiceManager\Factory\FactoryInterface;

class StandardFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new StandardForm(null, $options);
        $form->setTranslator($services->get('MvcTranslator'));
        return $form;
    }
}
