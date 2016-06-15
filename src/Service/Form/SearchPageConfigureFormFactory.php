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
        $translator = $serviceLocator->get('MvcTranslator');
        $formElementManager = $serviceLocator->get('FormElementManager');

        $form = new SearchPageConfigureForm(null, $this->options);
        $form->setTranslator($translator);
        $form->setFormElementManager($formElementManager);

        return $form;
    }

    public function setCreationOptions(array $options)
    {
        $this->options = $options;
    }
}
