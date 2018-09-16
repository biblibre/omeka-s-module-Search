<?php
namespace Search\View\Helper;

use Search\Mvc\Controller\Plugin\ApiSearch as ApiSearchPlugin;
use Zend\View\Helper\AbstractHelper;

class ApiSearch extends AbstractHelper
{
    /**
     * @var ApiSearchPlugin
     */
    protected $apiSearch;

    /**
     * @param ApiSearchPlugin $apiSearch
     */
    public function __construct(ApiSearchPlugin $apiSearch)
    {
        $this->apiSearch = $apiSearch;
    }

    /**
     * Execute a search API request via a querier if available, else the api.
     *
     * The arguments are the same than \Omeka\View\Helper\Api::search().
     * Some features of the Omeka api may not be available.
     *
     * @see \Omeka\Api\Manager::search()
     *
     * @param string $resource
     * @param array $data
     * @return \Omeka\Api\Response
     */
    public function __invoke($resource, array $data = [])
    {
        $apiSearch = $this->apiSearch;
        return $apiSearch($resource, $data);
    }
}
