<?php
namespace Search\Service\Form;

use Search\Form\BasicForm;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class BasicFormFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $elements)
    {
        $serviceLocator = $elements->getServiceLocator();
        $searchAdapterManager = $serviceLocator->get('Search\AdapterManager');

        $form = new BasicForm;
        $form->setTranslator($serviceLocator->get('MvcTranslator'));

        return $form;
    }
}
