<?php
namespace Search\Service\Form;

use Interop\Container\ContainerInterface;
use Search\Form\Admin\SearchPageConfigureSimpleForm;
use Zend\ServiceManager\Factory\FactoryInterface;

class SearchPageConfigureSimpleFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $formElementManager = $services->get('FormElementManager');
        $form = new SearchPageConfigureSimpleForm(null, $options);
        $form->setFormElementManager($formElementManager);
        return $form;
    }
}
