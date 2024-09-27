<?php

/*
 * Copyright BibLibre, 2016
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
    protected $totalResults;
    protected $resourceTotalResults = [];

    protected $results = [];
    protected $facetCounts = [];
    protected $highlights = [];

    public function setTotalResults($totalResults)
    {
        $this->totalResults = $totalResults;
    }

    public function getTotalResults()
    {
        return $this->totalResults;
    }

    public function setResourceTotalResults($resource, $totalResults)
    {
        $this->resourceTotalResults[$resource] = $totalResults;
    }

    public function getResourceTotalResults($resource)
    {
        if (!isset($this->resourceTotalResults[$resource])) {
            return 0;
        }

        return $this->resourceTotalResults[$resource];
    }

    public function addResult($resource, $result)
    {
        $this->results[$resource][] = $result;
    }

    public function getResults($resource)
    {
        return $this->results[$resource] ?? [];
    }

    public function addFacetCount($name, $value, $count)
    {
        $this->facetCounts[$name][] = [
            'value' => $value,
            'count' => $count,
        ];
    }

    public function getFacetCounts()
    {
        return $this->facetCounts;
    }

    /**
     * Add highlight (a text fragment containing a searched word or phrase)
     *
     * @param string $resource  resource name
     * @param int    $id        resource id
     * @param string $highlight highlight text, can contain HTML.
     */
    public function addHighlight(string $resource, int $id, string $highlight)
    {
        $this->highlights[$resource][$id][] = ['highlight' => $highlight];
    }

    /**
     * Get all highlights for a single result
     *
     * @param string $resource resource name
     * @param int    $id       resource id
     *
     * @return array[] an array of associative arrays
     *                 each associative array contains the following key:
     *                 * 'highlight' - contain the highlight text, which can contain HTML
     */
    public function getHighlights(string $resource, int $id): array
    {
        return $this->highlights[$resource][$id] ?? [];
    }
}
