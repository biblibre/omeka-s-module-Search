<?php

namespace Search\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Search\Api\Representation\SearchPageRepresentation;

class SearchCurrentPage extends AbstractHelper
{
    protected $searchPage;

    public function __invoke(): SearchPageRepresentation
    {
        return $this->searchPage;
    }

    public function setSearchPage(SearchPageRepresentation $searchPage)
    {
        $this->searchPage = $searchPage;
    }
}
