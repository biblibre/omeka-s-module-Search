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

class Query
{
    protected $query;
    protected $sort;
    protected $facetLimit;
    protected $facetFields = [];
    protected $filters = [];
    protected $dateRangeFilters = [];
    protected $offset = 0 ;
    protected $limit = 0;
    protected $resources = [];

    public function setQuery($query)
    {
        $this->query = $query;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function setFacetLimit($facetLimit)
    {
        $this->facetLimit = $facetLimit;
    }

    public function getFacetLimit()
    {
        return $this->facetLimit;
    }

    public function addFacetField($field)
    {
        $this->facetFields[] = $field;
    }

    public function getFacetFields()
    {
        return $this->facetFields;
    }

    public function addFilter($name, $value)
    {
        $this->filters[$name][] = $value;
    }

    public function getFilters()
    {
        return $this->filters;
    }

    public function addDateRangeFilter($name, $start, $end)
    {
        $this->dateRangeFilters[$name][] = [
            'start' => $start,
            'end' => $end,
        ];
    }

    public function getDateRangeFilters()
    {
        return $this->dateRangeFilters;
    }

    public function setSort($sort)
    {
        $this->sort = $sort;
    }

    public function getSort()
    {
        return $this->sort;
    }

    public function getOffset()
    {
        return $this->offset;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function setLimitPage($page, $rowCount)
    {
        $page     = ($page > 0)     ? $page     : 1;
        $rowCount = ($rowCount > 0) ? $rowCount : 1;
        $this->limit = (int) $rowCount;
        $this->offset = (int) $rowCount * ($page - 1);
    }

    public function setResources($resources)
    {
        $this->resources = $resources;
    }

    public function getResources()
    {
        return $this->resources;
    }
}
