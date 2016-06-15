<?php
namespace Search\Service\Form;

use Search\Form\Admin\SearchIndexForm;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SearchIndexFormFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $elements)
    {
        $serviceLocator = $elements->getServiceLocator();
        $searchAdapterManager = $serviceLocator->get('Search\AdapterManager');

        $form = new SearchIndexForm;
        $form->setTranslator($serviceLocator->get('MvcTranslator'));
        $form->setSearchAdapterManager($searchAdapterManager);

        return $form;
    }
}
