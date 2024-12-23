<?php
namespace Search\Service\Form;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Search\Form\Admin\SearchPageEditForm;

class SearchPageEditFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $api = $services->get('Omeka\ApiManager');
        $formAdapterManager = $services->get('Search\FormAdapterManager');
        $formElementManager = $services->get('FormElementManager');
        $viewHelperManager = $services->get('ViewHelperManager');

        $form = new SearchPageEditForm(null, $options);
        $form->setApiManager($api);
        $form->setFormAdapterManager($formAdapterManager);
        $form->setFormElementManager($formElementManager);
        $form->setUrlViewHelper($viewHelperManager->get('Url'));

        return $form;
    }
}
