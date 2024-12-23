<?php
namespace Search\Service\Form;

use Interop\Container\ContainerInterface;
use Search\Form\Admin\SearchIndexAddForm;
use Laminas\ServiceManager\Factory\FactoryInterface;

class SearchIndexAddFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $searchAdapterManager = $services->get('Search\AdapterManager');

        $form = new SearchIndexAddForm(null, $options ?? []);
        $form->setSearchAdapterManager($searchAdapterManager);

        return $form;
    }
}
