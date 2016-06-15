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
        $searchFormManager = $serviceLocator->get('Search\FormManager');

        $form = new SearchPageForm;
        $form->setTranslator($translator);
        $form->setApiManager($api);
        $form->setSearchFormManager($searchFormManager);

        return $form;
    }
}
