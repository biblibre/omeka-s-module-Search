<?php
namespace Search\Service\Form;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Search\Form\Admin\SearchPageForm;

class SearchPageFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $translator = $services->get('MvcTranslator');
        $api = $services->get('Omeka\ApiManager');
        $formAdapterManager = $services->get('Search\FormAdapterManager');

        $form = new SearchPageForm;
        $form->setTranslator($translator);
        $form->setApiManager($api);
        $form->setFormAdapterManager($formAdapterManager);

        return $form;
    }
}
