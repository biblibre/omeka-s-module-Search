<?php
namespace Search\Mvc\Controller\Plugin;

use Search\Api\Representation\SearchPageRepresentation;
use Search\View\Helper\SearchForm as SearchFormHelper;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class SearchForm extends AbstractPlugin
{
    /**
     * @var SearchFormHelper
     */
    protected $searchFormHelper;

    /**
     * @param SearchFormHelper $searchFormHelper
     */
    public function __construct(SearchFormHelper $searchFormHelper)
    {
        $this->searchFormHelper = $searchFormHelper;
    }

    /**
     * @param SearchPageRepresentation|null $searchPage
     * @return \Zend\Form\Form;
     */
    public function __invoke(SearchPageRepresentation $searchPage = null)
    {
        $searchForm = $this->searchFormHelper;
        return $searchForm($searchPage)->getForm();
    }
}
