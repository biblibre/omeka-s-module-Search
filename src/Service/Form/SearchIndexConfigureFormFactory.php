<?php
namespace Search\Service\Form;

use Search\Form\Admin\SearchIndexConfigureForm;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class SearchIndexConfigureFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $api = $services->get('Omeka\ApiManager');
        $translator = $services->get('MvcTranslator');

        $form = new SearchIndexConfigureForm(null, $options);
        $form->setTranslator($translator);
        $form->setApiManager($api);

        return $form;
    }
}
