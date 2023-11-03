<?php

namespace Search\Service\ViewHelper;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Search\View\Helper\SearchFacetValue;

class SearchFacetValueFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $facetValueRendererManager = $services->get('Search\FacetValueRendererManager');

        $searchFacetValue = new SearchFacetValue($facetValueRendererManager);

        return $searchFacetValue;
    }
}
