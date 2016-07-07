<?php

namespace Search\Service\Mvc\Controller\Plugin;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Search\Mvc\Controller\Plugin\SearchForm;

class SearchFormFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $plugins)
    {
        $services = $plugins->getServiceLocator();
        $viewHelpers = $services->get('ViewHelperManager');

        return new SearchForm($viewHelpers);
    }
}
