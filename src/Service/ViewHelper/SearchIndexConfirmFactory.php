<?php
namespace Search\Service\ViewHelper;

use Interop\Container\ContainerInterface;
use Search\View\Helper\SearchIndexConfirm;
use Zend\ServiceManager\Factory\FactoryInterface;

class SearchIndexConfirmFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new SearchIndexConfirm($services->get('FormElementManager'));
    }
}
