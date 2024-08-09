<?php

namespace Search\Feature;

use Search\Api\Representation\SearchPageRepresentation;

interface SummarizeQueryInterface
{
    /**
     * Translates search parameters (URL parameters) into a human-friendly
     * summary, to be displayed near the search results.
     *
     * @param array $data The search parameters
     *
     * @param SearchPageRepresentation $searchPage The search page where the search occured
     *
     * @return array[] An array of associative arrays, each containing the
     *                 following keys:
     *                 - 'name': (string) The name of a search field
     *                 - 'value': (string) The value of a search field, ie. what the user typed/selected
     */
    public function summarizeQuery(array $data, SearchPageRepresentation $searchPage): array;
}
