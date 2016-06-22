<?php

namespace Search\Test\Indexer;

use Omeka\Api\Representation\AbstractResourceRepresentation;
use Search\Indexer\AbstractIndexer;

class TestIndexer extends AbstractIndexer
{
    public function canIndex($resourceName)
    {
        return true;
    }

    public function clearIndex()
    {
    }

    public function indexResource(AbstractResourceRepresentation $resource)
    {
    }

    public function indexResources(array $resources)
    {
    }

    public function deleteResource($resourceName, $id)
    {
    }
}
