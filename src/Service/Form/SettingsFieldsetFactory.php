<?php
namespace Search\Service\Form;

use Interop\Container\ContainerInterface;
use Search\Form\SettingsFieldset;
use Zend\ServiceManager\Factory\FactoryInterface;

class SettingsFieldsetFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $fieldset = new SettingsFieldset(null, $options);
        $viewHelpers = $services->get('ViewHelperManager');
        $fieldset->setApi($viewHelpers->get('api'));
        $fieldset->setBasePath($viewHelpers->get('basePath'));
        return $fieldset;
    }
}
