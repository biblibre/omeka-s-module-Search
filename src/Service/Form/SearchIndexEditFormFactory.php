<?php
namespace Search\Service\Form;

use Search\Form\Admin\SearchIndexEditForm;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class SearchIndexEditFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $api = $services->get('Omeka\ApiManager');
        $searchAdapterManager = $services->get('Search\AdapterManager');

        $form = new SearchIndexEditForm(null, $options ?? []);
        $form->setApiManager($api);
        $form->setSearchAdapterManager($searchAdapterManager);

        return $form;
    }
}
