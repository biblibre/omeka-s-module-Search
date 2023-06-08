<?php
namespace Search\Service\Form;

use Search\Form\SaveQueryForm;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class SaveQueryFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new SaveQueryForm(null, $options);
        $viewHelperManager = $services->get('ViewHelperManager');
        $form->setUrlHelper($viewHelperManager->get('Url'));
        return $form;
    }
}
