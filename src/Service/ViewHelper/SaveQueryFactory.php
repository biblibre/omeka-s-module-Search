<?php

namespace Search\Service\ViewHelper;

use Search\Form\SaveQueryForm;
use Search\View\Helper\SaveQuery;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class SaveQueryFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $formElementManager = $services->get('FormElementManager');
        $saveQueryForm = $formElementManager->get(SaveQueryForm::class);
        $helper = new SaveQuery();
        $helper->setSaveQueryForm($saveQueryForm);
        return $helper;
    }
}
