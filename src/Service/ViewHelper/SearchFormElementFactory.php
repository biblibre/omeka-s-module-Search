<?php

namespace Search\Service\ViewHelper;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Search\View\Helper\SearchFormElement;

class SearchFormElementFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $searchFormElementManager = $services->get('Search\FormElementManager');

        $searchFormElement = new SearchFormElement($searchFormElementManager);

        return $searchFormElement;
    }
}
