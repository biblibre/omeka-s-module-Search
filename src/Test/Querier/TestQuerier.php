<?php

namespace Search\Test\Querier;

use Search\Querier\AbstractQuerier;
use Search\Query;
use Search\Response;

class TestQuerier extends AbstractQuerier
{
    public function query(Query $query)
    {
        $response = new Response;

        $response->setTotalResults(0);
        foreach ($query->getResources() as $resource) {
            $response->setResourceTotalResults($resource, 0);
        }

        return $response;
    }
}
