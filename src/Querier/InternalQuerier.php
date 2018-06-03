<?php

namespace Search\Querier;

use Search\Querier\Exception\QuerierException;
use Search\Query;
use Search\Response;

class InternalQuerier extends AbstractQuerier
{
    public function query(Query $query)
    {
        // TODO Normalize search url arguments. Here, the ones from Solr are taken.

        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');

        /** @var \Omeka\Api\Manager $api */
        $api = $services->get('Omeka\ApiManager');

        // The data are the ones used to build the query with the standard api.
        // Queries are multiple (one by resource type and by facet).
        // Note: the query is a scalar one, so final events are not triggered.
        // TODO Do a full api reference search or only scalar ids?
        $data = [];

        $q = $query->getQuery();
        $q = trim($q);
        if (strlen($q)) {
            $data['property'][] = [
                'joiner' => 'and',
                'property' => '',
                'type' => 'in',
                'text' => $q,
            ];
        }

        $indexerResourceTypes = $this->getSetting('resources', []);
        $resourceTypes = $query->getResources() ?: $indexerResourceTypes;
        $resourceTypes = array_intersect($resourceTypes, $indexerResourceTypes);
        if (empty($resourceTypes)) {
            return new Response();
        }

        $site = $query->getSite();
        if ($site) {
            $data['site_id'] = $site->id();
        }

        $filters = $query->getFilters();
        foreach ($filters as $name => $values) {
            foreach ($values as $value) {
                $data['property'][] = [
                    'joiner' => 'and',
                    'property' => $name,
                    'type' => 'in',
                    'text' => $value,
                ];
            }
        }

        // TODO Manage the date range filters (one or two properties?).
        /*
         $dateRangeFilters = $query->getDateRangeFilters();
        foreach ($dateRangeFilters as $name => $filterValues) {
            foreach ($filterValues as $filterValue) {
                $start = $filterValue['start'] ? $filterValue['start'] : '*';
                $end = $filterValue['end'] ? $filterValue['end'] : '*';
                $data['property'][] = [
                    'joiner' => 'and',
                    'property' => 'dcterms:date',
                    'type' => 'gte',
                    'text' => $start,
                ];
                $data['property'][] = [
                    'joiner' => 'and',
                    'property' => 'dcterms:date',
                    'type' => 'lte',
                    'text' => $end,
                ];
            }
        }
        */

        $sort = $query->getSort();
        if ($sort) {
            list($sortField, $sortOrder) = explode(' ', $sort);
            $data['sort_by'] = $sortField;
            $data['sort_order'] = $sortOrder == 'desc' ? 'desc' : 'asc';
        }

        $limit = $query->getLimit();
        if ($limit) {
            $data['limit'] = $limit;
        }

        $offset = $query->getOffset();
        if ($offset) {
            $data['offset'] = $offset;
        }

        $response = new Response;

        foreach ($resourceTypes as $resourceType) {
            try {
                $apiResponse = $api->search($resourceType, $data, ['returnScalar' => 'id']);
            } catch (\Omeka\Api\Exception\ExceptionInterface $e) {
                throw new QuerierException($e->getMessage(), $e->getCode(), $e);
            }
            $totalResults = $apiResponse->getTotalResults();
            $response->setResourceTotalResults($resourceType, $totalResults);
            $response->setTotalResults($response->getTotalResults() + $totalResults);
            if ($totalResults) {
                $result = array_map(function ($v) {
                    return ['id' => $v];
                }, $apiResponse->getContent());
            } else {
                $result = [];
            }
            $response->addResults($resourceType, $result);
        }

        return $response;
    }
}
