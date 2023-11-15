<?php
namespace Search\Service\FacetValueRenderer;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Search\FacetValueRenderer\ResourceTitle;

class ResourceTitleFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $api = $services->get('Omeka\ApiManager');

        $resourceTitle = new ResourceTitle($api);

        return $resourceTitle;
    }
}
