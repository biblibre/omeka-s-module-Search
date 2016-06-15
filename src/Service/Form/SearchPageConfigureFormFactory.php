<?php
namespace Search\Service\Form;

use Search\Form\Admin\SearchPageConfigureForm;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SearchPageConfigureFormFactory implements FactoryInterface
{
    protected $options = [];

    public function createService(ServiceLocatorInterface $elements)
    {
        $serviceLocator = $elements->getServiceLocator();
        $form = new SearchPageConfigureForm(null, $this->options);
        $form->setTranslator($serviceLocator->get('MvcTranslator'));
        return $form;
    }

    public function setCreationOptions(array $options)
    {
        $this->options = $options;
    }
}
