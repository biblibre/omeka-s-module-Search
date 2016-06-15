<?php
namespace Search\Service\Form;

use Search\Form\Admin\SearchPageForm;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SearchPageFormFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $elements)
    {
        $serviceLocator = $elements->getServiceLocator();
        $translator = $serviceLocator->get('MvcTranslator');
        $api = $serviceLocator->get('Omeka\ApiManager');
        $formAdapterManager = $serviceLocator->get('Search\FormAdapterManager');

        $form = new SearchPageForm;
        $form->setTranslator($translator);
        $form->setApiManager($api);
        $form->setFormAdapterManager($formAdapterManager);

        return $form;
    }
}
