<?php

namespace Search\Test\Adapter;

use Zend\Form\Fieldset;
use Search\Adapter\AbstractAdapter;

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
}
