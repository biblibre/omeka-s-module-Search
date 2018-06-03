<?php

/*
 * Copyright BibLibre, 2016
 * Copyright Daniel Berthereau, 2018
 *
 * This software is governed by the CeCILL license under French law and abiding
 * by the rules of distribution of free software.  You can use, modify and/ or
 * redistribute the software under the terms of the CeCILL license as circulated
 * by CEA, CNRS and INRIA at the following URL "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and rights to copy, modify
 * and redistribute granted by the license, users are provided only with a
 * limited warranty and the software's author, the holder of the economic
 * rights, and the successive licensors have only limited liability.
 *
 * In this respect, the user's attention is drawn to the risks associated with
 * loading, using, modifying and/or developing or reproducing the software by
 * the user in light of its specific status of free software, that may mean that
 * it is complicated to manipulate, and that also therefore means that it is
 * reserved for developers and experienced professionals having in-depth
 * computer knowledge. Users are therefore encouraged to load and test the
 * software's suitability as regards their requirements in conditions enabling
 * the security of their systems and/or data to be ensured and, more generally,
 * to use and operate it in the same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
 */

namespace Search;

class Response
{
    /**
     * @var int
     */
    protected $totalResults = 0;

    /**
     * @var array
     */
    protected $resourceTotalResults = [];

    /**
     * @var array
     */
    protected $results = [];

    /**
     * @var array
     */
    protected $facetCounts = [];

    /**
     * @param int $totalResults
     */
    public function setTotalResults($totalResults)
    {
        $this->totalResults = (int) $totalResults;
    }

    /**
     * @return int
     */
    public function getTotalResults()
    {
        return $this->totalResults;
    }

    /**
     * @param string $resourceType The resource type ("items", "item_sets"…).
     * @param int $totalResults
     */
    public function setResourceTotalResults($resourceType, $totalResults)
    {
        $this->resourceTotalResults[$resourceType] = (int) $totalResults;
    }

    /**
     * @param string $resourceType The resource type ("items", "item_sets"…).
     * @return int
     */
    public function getResourceTotalResults($resourceType)
    {
        return isset($this->resourceTotalResults[$resourceType])
            ? $this->resourceTotalResults[$resourceType]
            : 0;
    }

    /**
     * Store a list of results.
     *
     * @param string $resourceType The resource type ("items", "item_sets"…).
     * @param array $results Each result is an array with "id" as key.
     */
    public function addResults($resourceType, $results)
    {
        $this->results[$resourceType] = isset($this->results[$resourceType])
            ? array_merge($this->results[$resourceType], array_values($results))
            : array_values($results);
    }

    /**
     * Store a result.
     *
     * @param string $resourceType The resource type ("items", "item_sets"…).
     * @param array $result
     */
    public function addResult($resourceType, $result)
    {
        $this->results[$resourceType][] = $result;
    }

    /**
     * Get stored results.
     *
     * @param string $resourceType The resource type ("items", "item_sets"…).
     * @return array
     */
    public function getResults($resourceType)
    {
        return isset($this->results[$resourceType])
            ? $this->results[$resourceType]
            : [];
    }

    /**
     * Store the result for a facet.
     *
     * @param string $name
     * @param string $value
     * @param int $count
     */
    public function addFacetCount($name, $value, $count)
    {
        $this->facetCounts[$name][] = [
            'value' => $value,
            'count' => $count,
        ];
    }

    /**
     * Get all the facet counts.
     *
     * @return array
     */
    public function getFacetCounts()
    {
        return $this->facetCounts;
    }
}
