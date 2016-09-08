<?php
namespace Search\Service\Form;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Search\Form\Admin\SearchIndexForm;

class SearchIndexFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $searchAdapterManager = $services->get('Search\AdapterManager');

        $form = new SearchIndexForm;
        $form->setTranslator($services->get('MvcTranslator'));
        $form->setSearchAdapterManager($searchAdapterManager);

        return $form;
    }
}
