<?php

namespace Search\Mvc\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\View\HelperPluginManager;
use Search\Api\Representation\SearchPageRepresentation;

class SearchForm extends AbstractPlugin
{
    /**
     * @var HelperPluginManager
     */
    protected $viewHelpers;

    public function __construct(HelperPluginManager $viewHelpers)
    {
        $this->viewHelpers = $viewHelpers;
    }

    public function __invoke(SearchPageRepresentation $searchPage)
    {
        $searchForm = $this->viewHelpers->get('searchForm');
        return $searchForm($searchPage)->getForm();
    }
}
