<?php
namespace Search\Service\Form;

use Interop\Container\ContainerInterface;
use Search\Form\StandardConfigForm;
use Laminas\ServiceManager\Factory\FactoryInterface;

class StandardConfigFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new StandardConfigForm(null, $options);
        $form->setTranslator($services->get('MvcTranslator'));
        return $form;
    }
}
