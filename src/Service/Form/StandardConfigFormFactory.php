<?php
namespace Search\Service\Form;

use Interop\Container\ContainerInterface;
use Search\Form\StandardConfigForm;
use Laminas\ServiceManager\Factory\FactoryInterface;

class StandardConfigFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $viewHelperManager = $services->get('ViewHelperManager');
        $searchFormElementManager = $services->get('Search\FormElementManager');

        $form = new StandardConfigForm(null, $options);
        $form->setUrlViewHelper($viewHelperManager->get('Url'));
        $form->setSearchFormElementManager($searchFormElementManager);

        return $form;
    }
}
