<?php

namespace Search\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\Mvc\Application;
use Omeka\Api\Manager as ApiManager;

class FacetLabel extends AbstractHelper
{
    protected $application;
    protected $api;

    protected $availableFacetFields;

    public function __construct(Application $application, ApiManager $api)
    {
        $this->application = $application;
        $this->api = $api;
    }

    public function __invoke($name)
    {
        if (!isset($this->availableFacetFields)) {
            $mvcEvent = $this->application->getMvcEvent();
            $routeMatch = $mvcEvent->getRouteMatch();

            $response = $this->api->read('search_pages', $routeMatch->getParam('id'));
            $searchPage = $response->getContent();
            $searchAdapter = $searchPage->index()->adapter();

            $this->availableFacetFields = $searchAdapter->getAvailableFacetFields();
        }

        if (isset($this->availableFacetFields[$name])) {
            return $this->availableFacetFields[$name]['label'];
        }
    }
}
