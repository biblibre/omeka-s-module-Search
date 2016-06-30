<?php

namespace Search\Service\Form\Element;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Search\Form\Element\SearchPageSelect;

class SearchPageSelectFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $elements)
    {
        $serviceLocator = $elements->getServiceLocator();
        $apiManager = $serviceLocator->get('Omeka\ApiManager');

        $element = new SearchPageSelect;
        $element->setApiManager($apiManager);

        return $element;
    }
}
