<?php

/*
 * Copyright BibLibre, 2016
 *
 * This software is governed by the CeCILL license under French law and abiding
 * by the rules of distribution of free software. You can use, modify and/ or
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

use Omeka\Api\Representation\SiteRepresentation;

class Query
{
    /**
     * @var string
     */
    protected $query = '';

    /**
     * @var string
     */
    protected $sort = '';

    /**
     * @var int
     */
    protected $facetLimit = 0;

    /**
     * @var array
     */
    protected $facetFields = [];

    /**
     * @var array
     */
    protected $filters = [];

    /**
     * @var array
     */
    protected $dateRangeFilters = [];

    /**
     * @var int
     */
    protected $offset = 0;

    /**
     * @var int
     */
    protected $limit = 0;

    /**
     * @var bool
     */
    protected $isPublic;

    /**
     * @var array
     */
    protected $resources = [];

    /**
     * @var SiteRepresentation
     */
    protected $site;

    /**
     * @param string $query
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param int $facetLimit
     */
    public function setFacetLimit($facetLimit)
    {
        $this->facetLimit = (int) $facetLimit;
    }

    /**
     * @return int
     */
    public function getFacetLimit()
    {
        return $this->facetLimit;
    }

    /**
     * @param string $field
     */
    public function addFacetField($field)
    {
        $this->facetFields[] = $field;
    }

    /**
     * Get the flat list of fields to use as facet.
     *
     * @return array
     */
    public function getFacetFields()
    {
        return $this->facetFields;
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function addFilter($name, $value)
    {
        $this->filters[$name][] = $value;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @param string $name
     * @param string $start
     * @param string $end
     */
    public function addDateRangeFilter($name, $start, $end)
    {
        $this->dateRangeFilters[$name][] = [
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * @return array
     */
    public function getDateRangeFilters()
    {
        return $this->dateRangeFilters;
    }

    /**
     * @param string $sort The field and the direction ("asc" or "desc")
     * separated by a space.
     */
    public function setSort($sort)
    {
        $this->sort = $sort;
    }

    /**
     * @return string The field and the direction ("asc" or "desc") separated by
     * a space.
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param int $page
     * @param int $rowCount
     */
    public function setLimitPage($page, $rowCount)
    {
        $page = ($page > 0) ? $page : 1;
        $rowCount = ($rowCount > 0) ? $rowCount : 1;
        $this->limit = (int) $rowCount;
        $this->offset = (int) $rowCount * ($page - 1);
    }

    /**
     * @param array $isPublic
     */
    public function setIsPublic($isPublic)
    {
        $this->isPublic = $isPublic;
    }

    /**
     * @return bool
     */
    public function getIsPublic()
    {
        return $this->isPublic;
    }

    /**
     * @param array $resources The resource types are "items" and "item_sets".
     */
    public function setResources($resources)
    {
        $this->resources = $resources;
    }

    /**
     * @return array
     */
    public function getResources()
    {
        return $this->resources;
    }

    /**
     * @param SiteRepresentation $site
     */
    public function setSite(SiteRepresentation $site)
    {
        $this->site = $site;
    }

    /**
     * @return \Omeka\Api\Representation\SiteRepresentation
     */
    public function getSite()
    {
        return $this->site;
    }
}
