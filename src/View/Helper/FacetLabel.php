<?php

namespace Search\View\Helper;

use Zend\View\Helper\AbstractHelper;

class FacetLabel extends AbstractHelper
{
    protected $availableFacetFields;

    public function __invoke($name)
    {
        if (!isset($this->availableFacetFields)) {
            $view = $this->getView();
            $serviceLocator = $view->getHelperPluginManager()->getServiceLocator();
            $searchAdapterManager = $serviceLocator->get('Search\AdapterManager');
            $mvcEvent = $serviceLocator->get('Application')->getMvcEvent();
            $api = $serviceLocator->get('Omeka\ApiManager');

            $routeMatch = $mvcEvent->getRouteMatch();
            $response = $api->read('search_pages', $routeMatch->getParam('id'));
            $searchPage = $response->getContent();
            $searchAdapter = $searchPage->index()->adapter();
            $this->availableFacetFields = $searchAdapter->getAvailableFacetFields();
        }

        if (isset($this->availableFacetFields[$name])) {
            return $this->availableFacetFields[$name]['label'];
        }
    }
}
