<?php
namespace Search\Service\Form\Element;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Search\Form\Element\FacetValueRendererSelect;

class FacetValueRendererSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $facetValueRendererManager = $services->get('Search\FacetValueRendererManager');

        $element = new FacetValueRendererSelect(null, $options ?? []);
        $element->setFacetValueRendererManager($facetValueRendererManager);

        return $element;
    }
}
