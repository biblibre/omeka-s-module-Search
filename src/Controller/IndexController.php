<?php

/*
 * Copyright BibLibre, 2016-2017
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

namespace Search\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Omeka\Mvc\Exception\RuntimeException;
use Omeka\Stdlib\Paginator;
use Search\Querier\Exception\QuerierException;

class IndexController extends AbstractActionController
{
    protected $page;
    protected $index;

    public function searchAction()
    {
        $response = $this->api()->read('search_pages', $this->params('id'));
        $this->page = $response->getContent();
        $index_id = $this->page->index()->id();

        $form = $this->searchForm($this->page);

        $view = new ViewModel;
        $site = $this->currentSite();
        $params = $this->params()->fromQuery();
        if (empty($params)) {
            return $view;
        }

        $form->setData($params);
        if (!$form->isValid()) {
            $this->messenger()->addError('There was an error during validation');
            return $view;
        }

        $searchPageSettings = $this->page->settings();
        $searchFormSettings = [];
        if (isset($searchPageSettings['form'])) {
            $searchFormSettings = $searchPageSettings['form'];
        }

        $formAdapter = $this->page->formAdapter();
        if (!isset($formAdapter)) {
            $formAdapterName = $this->page->formAdapterName();
            $msg = sprintf("Form adapter '%s' not found", $formAdapterName);
            throw new RuntimeException($msg);
        }

        $query = $formAdapter->toQuery($params, $searchFormSettings);
        $response = $this->api()->read('search_indexes', $index_id);
        $this->index = $response->getContent();

        $querier = $this->index->querier();

        $indexSettings = $this->index->settings();
        if (array_key_exists('resource_type', $params)) {
            $resource_type = $params['resource_type'];
            if (!is_array($resource_type)) {
                $resource_type = [$resource_type];
            }
            $query->setResources($resource_type);
        } else {
            $query->setResources($indexSettings['resources']);
        }

        $settings = $this->page->settings();
        foreach ($settings['facets'] as $facet) {
            $query->addFacetField($facet['name']);
        }
        if (isset($settings['facet_limit'])) {
            $query->setFacetLimit($settings['facet_limit']);
        }

        if (isset($params['limit'])) {
            foreach ($params['limit'] as $name => $values) {
                foreach ($values as $value) {
                    $query->addFacetFilter($name, $value);
                }
            }
        }

        $query->setSite($this->currentSite());

        $sortOptions = $this->getSortOptions();

        if (isset($params['sort'])) {
            $sort = $params['sort'];
        } else {
            reset($sortOptions);
            $sort = key($sortOptions);
        }

        $query->setSort($sort);
        $page_number = $params['page'] ?? 1;
        $this->setPagination($query, $page_number);
        try {
            $response = $querier->query($query);
        } catch (QuerierException $e) {
            $this->messenger()->addError('Query error: ' . $e->getMessage());
            return $view;
        }

        $facetCounts = $response->getFacetCounts();
        $facets = [];
        foreach ($settings['facets'] as $facet) {
            $name = $facet['name'];
            if (array_key_exists($name, $facetCounts)) {
                $facets[$name] = $facetCounts[$name];
            }
        }

        $saveQueryParam = $this->page->settings()['save_queries'] ?? false;
        if ($settings['show_search_summary']) {
            $summarizeQuery = $formAdapter->summarizeQuery($params, $this->page);
        }

        $queryParams = json_encode($this->params()->fromQuery());
        $searchPageId = $this->page->id();
        $siteId = $site->id();

        $totalResults = array_map(function ($resource) use ($response) {
            return $response->getResourceTotalResults($resource);
        }, $indexSettings['resources']);
        $this->paginator(max($totalResults), $page_number);
        $view->setVariable('query', $query);
        $view->setVariable('site', $site);
        $view->setVariable('response', $response);
        $view->setVariable('facets', $facets);
        $view->setVariable('saveQueryParam', $saveQueryParam);
        $view->setVariable('sortOptions', $sortOptions);
        $view->setVariable('queryParams', $queryParams);
        $view->setVariable('searchPageId', $searchPageId);
        if (isset($summarizeQuery)) {
            $view->setVariable('summarizeQuery', $summarizeQuery);
        }

        return $view;
    }

    protected function setPagination($query, $page)
    {
        $per_page = $this->settings()->get('pagination_per_page', Paginator::PER_PAGE);
        $query->setLimitPage($page, $per_page);
    }

    protected function getSortOptions()
    {
        $sortOptions = [];

        $sortFields = $this->index->adapter()->getAvailableSortFields($this->index);
        $sortFieldsMap = array_combine(array_column($sortFields, 'name'), $sortFields);
        $settings = $this->page->settings();
        foreach ($settings['sort_fields'] as $sort_field) {
            $name = $sort_field['name'];

            $label = trim($sort_field['label'] ?? '');
            if ($label === '') {
                $label = trim($sortFieldsMap[$name]['label'] ?? '');
            }
            if ($label === '') {
                $label = $name;
            }

            $sortOptions[$name] = $label;
        }

        return $sortOptions;
    }
}
