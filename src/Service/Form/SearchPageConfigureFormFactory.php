<?php
namespace Search\Service\Form;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use Search\Form\Admin\SearchPageConfigureForm;

class SearchPageConfigureFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $formElementManager = $services->get('FormElementManager');
        $form = new SearchPageConfigureForm(null, $options);
        $form->setFormElementManager($formElementManager);
        return $form;
    }
}
