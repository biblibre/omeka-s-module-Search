<?php

namespace Search\Indexer;

use Omeka\Entity\Resource;
use Search\Api\Representation\SearchIndexRepresentation;
use Zend\Log\LoggerAwareTrait;
use Zend\ServiceManager\ServiceLocatorInterface;

class NoopIndexer implements IndexerInterface
{
    use LoggerAwareTrait;

    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
    }

    public function setSearchIndex(SearchIndexRepresentation $index)
    {
    }

    public function canIndex($resourceName)
    {
        return false;
    }

    public function clearIndex()
    {
    }

    public function indexResource(Resource $resource)
    {
    }

    public function indexResources(array $resources)
    {
    }

    public function deleteResource($resourceName, $id)
    {
    }
}
