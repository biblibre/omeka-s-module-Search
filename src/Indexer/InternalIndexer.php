<?php

namespace Search\Indexer;

class InternalIndexer extends NoopIndexer
{
    public function canIndex($resourceName)
    {
        // The answer should be true, even if there is no index.
        return in_array($resourceName, ['items', 'item_sets']);
    }
}
