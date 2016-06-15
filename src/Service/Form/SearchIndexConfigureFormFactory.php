<?php
namespace Search\Service\Form;

use Search\Form\Admin\SearchIndexConfigureForm;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SearchIndexConfigureFormFactory implements FactoryInterface
{
    protected $options = [];

    public function createService(ServiceLocatorInterface $elements)
    {
        $serviceLocator = $elements->getServiceLocator();
        $api = $serviceLocator->get('Omeka\ApiManager');

        $form = new SearchIndexConfigureForm(null, $this->options);
        $form->setTranslator($serviceLocator->get('MvcTranslator'));
        $form->setApiManager($api);

        return $form;
    }

    public function setCreationOptions($options)
    {
        $this->options = $options;
    }
}
