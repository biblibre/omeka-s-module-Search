<?php
namespace Search\Service\Form;

use Interop\Container\ContainerInterface;
use Search\Form\Admin\SearchPageAddForm;
use Laminas\ServiceManager\Factory\FactoryInterface;

class SearchPageAddFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $api = $services->get('Omeka\ApiManager');
        $formAdapterManager = $services->get('Search\FormAdapterManager');

        $form = new SearchPageAddForm(null, $options ?? []);
        $form->setApiManager($api);
        $form->setFormAdapterManager($formAdapterManager);

        return $form;
    }
}
