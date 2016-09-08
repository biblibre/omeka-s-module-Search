<?php
namespace Search\Service\Form;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Search\Form\BasicForm;

class BasicFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new BasicForm;
        $form->setTranslator($services->get('MvcTranslator'));

        return $form;
    }
}
