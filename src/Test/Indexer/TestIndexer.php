<?php

namespace Search\Test\Indexer;

use Omeka\Entity\Resource;
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
