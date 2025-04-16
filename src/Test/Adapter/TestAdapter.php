<?php

namespace Search\Test\Adapter;

use Laminas\Form\Fieldset;
use Search\Adapter\AbstractAdapter;
use Search\Api\Representation\SearchIndexRepresentation;

class TestAdapter extends AbstractAdapter
{
    public function getLabel()
    {
        return 'TestAdapter';
    }

    public function getConfigFieldset()
    {
        return new Fieldset;
    }

    public function getIndexerClass()
    {
        return 'Search\Test\Indexer\TestIndexer';
    }

    public function getQuerierClass()
    {
        return 'Search\Test\Querier\TestQuerier';
    }

    public function getAvailableFacetSorts(SearchIndexRepresentation $index): array
    {
        return [];
    }
}
