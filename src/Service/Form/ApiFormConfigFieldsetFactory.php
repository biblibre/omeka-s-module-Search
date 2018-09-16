<?php
namespace Search\Service\Form;

use Search\Form\Admin\ApiFormConfigFieldset;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class ApiFormConfigFieldsetFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new ApiFormConfigFieldset(null, $options);
        $form->setApiManager($services->get('Omeka\ApiManager'));
        return $form;
    }
}
