<?php
namespace Search\Service\Form;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Search\Form\Admin\SearchPageConfigureForm;

class SearchPageConfigureFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $translator = $services->get('MvcTranslator');
        $formElementManager = $services->get('FormElementManager');
        $viewHelperManager = $services->get('ViewHelperManager');

        $form = new SearchPageConfigureForm(null, $options);
        $form->setTranslator($translator);
        $form->setFormElementManager($formElementManager);
        $form->setUrlViewHelper($viewHelperManager->get('Url'));

        return $form;
    }
}
