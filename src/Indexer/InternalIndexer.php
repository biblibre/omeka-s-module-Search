<?php

namespace Search\Indexer;

use Omeka\Entity\Resource;

class InternalIndexer extends AbstractIndexer
{
    public function canIndex($resourceName)
    {
        return in_array($resourceName, ['items', 'item_sets']);
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

    public function deleteResource($resourceName, $resourceId)
    {
    }
}
