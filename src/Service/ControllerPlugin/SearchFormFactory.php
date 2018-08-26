<?php
namespace Search\Service\ControllerPlugin;

use Interop\Container\ContainerInterface;
use Search\Mvc\Controller\Plugin\SearchForm;
use Zend\ServiceManager\Factory\FactoryInterface;

class SearchFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new SearchForm(
            $services->get('ViewHelperManager')->get('searchForm')
        );
    }
}
