<?php
namespace Search\Service\Form;

use Search\Form\FacetForm;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class FacetFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new FacetForm(null, $options ?? []);
    }
}
