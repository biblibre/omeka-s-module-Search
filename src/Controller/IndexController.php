<?php

/*
 * Copyright BibLibre, 2016-2017
 * Copyright Daniel Berthereau, 2017-2018
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

use Omeka\Mvc\Exception\RuntimeException;
use Omeka\Stdlib\Paginator;
use Search\Api\Representation\SearchIndexRepresentation;
use Search\Api\Representation\SearchPageRepresentation;
use Search\Querier\Exception\QuerierException;
use Zend\EventManager\Event;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    /**
     * @var SearchPageRepresentation
     */
    protected $page;

    /**
     * @var SearchIndexRepresentation
     */
    protected $index;

    public function searchAction()
    {
        $pageId = (int) $this->params('id');
        $isAdmin = $this->params()->fromRoute('__ADMIN__');
        if ($isAdmin) {
            $site = null;
        } else {
            $site = $this->currentSite();
            $siteSearchPages = $this->siteSettings()->get('search_pages', []);
            if (!in_array($pageId, $siteSearchPages)) {
                return $this->notFoundAction();
            }
        }

        $view = new ViewModel;
        $view->setVariable('isPartial', $isAdmin);
        $api = $this->api();
        $response = $api->read('search_pages', $pageId);
        $this->page = $response->getContent();
        $page = $this->page;

        /** @var \Search\FormAdapter\FormAdapterInterface $formAdapter */
        $formAdapter = $page->formAdapter();
        if (!$formAdapter) {
            $formAdapterName = $page->formAdapterName();
            $msg = sprintf('Form adapter "%s" not found', $formAdapterName); // @translate
            throw new RuntimeException($msg);
        }

        $request = $this->params()->fromQuery();

        // If the page is empty (json api request), there is no form.
        /** @var \Zend\Form\Form $form */
        $form = $this->searchForm($page);
        $jsonQuery = empty($form);

        // Here, an empty query is not allowed. To allow it, add a useless arg.
        if (empty($request)) {
            if ($jsonQuery) {
                return new JsonModel([
                    'status' => 'error',
                    'message' => 'No query.', // @translate
                ]);
            }
            return $view;
        }

        // TODO Validate api query too.
        if (!$jsonQuery) {
            $form->setData($request);
            if (!$form->isValid()) {
                $messages = $form->getMessages();
                if (isset($messages['csrf'])) {
                    $this->messenger()->addError('Invalid or missing CSRF token'); // @translate
                } else {
                    $this->messenger()->addError('There was an error during validation'); // @translate
                }
                return $view;
            }
            // Get the filtered request, but keep the pagination and sort params,
            // that are not managed by the form.
            $request = $form->getData() + $this->filterExtraParams($request);
        }

        $searchPageSettings = $page->settings();
        $searchFormSettings = isset($searchPageSettings['form'])
            ? $searchPageSettings['form']
            : [];

        /** @var \Search\Query $query */
        $query = $formAdapter->toQuery($request, $searchFormSettings);

        // Add global parameters.

        $index = $this->index = $page->index();
        $indexSettings = $index->settings();

        if (!$this->identity()) {
            $query->setIsPublic(true);
        }

        if ($site) {
            $query->setSiteId($site->id());
        }

        if (array_key_exists('resource-type', $request)) {
            $resourceType = $request['resource-type'];
            if (!is_array($resourceType)) {
                $resourceType = [$resourceType];
            }
            $query->setResources($resourceType);
        } else {
            $query->setResources($indexSettings['resources']);
        }

        // Don't sort if it's already managed by the form, like the api form.
        $sortOptions = $this->getSortOptions();
        $sort = $query->getSort();
        if (!is_null($sort)) {
            if (isset($request['sort']) && isset($sortOptions[$request['sort']])) {
                $sort = $request['sort'];
            } else {
                reset($sortOptions);
                $sort = key($sortOptions);
            }
            $query->setSort($sort);
        }

        // Note: the global limit is managed via the pagination.
        $pageNumber = isset($request['page']) && $request['page'] > 0 ? (int) $request['page'] : 1;
        $perPage = isset($request['per_page']) && $request['per_page'] > 0
            ? (int) $request['per_page']
            : (int) $this->settings()->get('pagination_per_page', Paginator::PER_PAGE);
        $query->setLimitPage($pageNumber, $perPage);

        $settings = $page->settings();
        $hasFacets = !empty($settings['facets']);
        if ($hasFacets) {
            foreach ($settings['facets'] as $name => $facet) {
                if ($facet['enabled']) {
                    $query->addFacetField($name);
                }
            }
            if (isset($settings['facet_limit'])) {
                $query->setFacetLimit($settings['facet_limit']);
            }
            if (!empty($request['limit']) && is_array($request['limit'])) {
                foreach ($request['limit'] as $name => $values) {
                    foreach ($values as $value) {
                        $query->addFilter($name, $value);
                    }
                }
            }
        }

        // TODO Use a generic querier as target for the event?
        $eventManager = $this->getEventManager();
        $eventArgs = $eventManager->prepareArgs([
            'search_page' => $page,
            'request' => $request,
            'query' => $query,
        ]);
        $eventManager->triggerEvent(new Event('search.query.pre', $this, $eventArgs));
        $query = $eventArgs['query'];

        // Send the query to the search engine.
        $querier = $index->querier();
        try {
            $response = $querier->query($query);
        } catch (QuerierException $e) {
            $message = sprintf('Query error: %s', $e->getMessage()); // @translate
            if ($jsonQuery) {
                return new JsonModel(['status' => 'error', 'message' => $message]);
            }
            $this->messenger()->addError($message);
            return $view;
        }

        if ($hasFacets) {
            $facets = $response->getFacetCounts();
            $facets = $this->sortFieldsByWeight($facets, 'facets');
        } else {
            $facets = [];
        }

        $totalResults = array_map(function ($resource) use ($response) {
            return $response->getResourceTotalResults($resource);
        }, $indexSettings['resources']);
        $this->paginator(max($totalResults), $pageNumber);

        if ($jsonQuery) {
            $result = [];
            foreach ($indexSettings['resources'] as $resource) {
                $result[$resource] = $response->getResults($resource);
            }
            return new JsonModel($result);
        }

        // Form is not set in the view.
        // $view->setVariable('form', $form);
        $view->setVariable('query', $query);
        $view->setVariable('site', $site);
        $view->setVariable('response', $response);
        $view->setVariable('facets', $facets);
        $view->setVariable('sortOptions', $sortOptions);
        return $view;
    }

    /**
     * Filter the pagination and sort params from the request.
     *
     * @todo Warning: "limit" is used as limit (int) of results and as filter for facets (array).
     *
     * @param array $request
     * @return array
     */
    protected function filterExtraParams(array $request)
    {
        $limitFacetRequest = [];
        if (!empty($request['limit']) && is_array($request['limit'])) {
            $limitFacetRequest['limit'] = $request['limit'];
        }

        $paginationRequest = array_map('intval', array_filter(array_intersect_key(
            $request,
            // @see \Omeka\Api\Adapter\AbstractEntityAdapter::limitQuery().
            ['page' => null, 'per_page' => null, 'limit' => null, 'offset' => null]
        )));

        // No filter neither cast here, but checked after.
        $sortRequest = array_intersect_key(
            $request,
            [
                // @see \Omeka\Api\Adapter\AbstractEntityAdapter::search().
                'sort_by' => null, 'sort_order' => null,
                // Used by Search.
                'resource-type' => null, 'sort' => null,
            ]
        );

        return $limitFacetRequest + $paginationRequest + $sortRequest;
    }

    /**
     * Normalize the sort options of the index.
     *
     * @todo Normalize the sort options when the index or page is hydrated.
     *
     * @return array
     */
    protected function getSortOptions()
    {
        $sortOptions = [];

        $settings = $this->page->settings();
        if (empty($settings['sort_fields'])) {
            return [];
        }

        $indexAdapter = $this->index->adapter();
        if (empty($indexAdapter)) {
            return [];
        }
        $sortFields = $this->index->adapter()->getAvailableSortFields($this->index);
        foreach ($settings['sort_fields'] as $name => $sortField) {
            if (!$sortField['enabled']) {
                // A break is possible, because now, the sort fields are ordered
                // when they are saved.
                break;
            }
            if (!empty($sortField['display']['label'])) {
                $label = $sortField['display']['label'];
            } elseif (!empty($sortFields[$name]['label'])) {
                $label = $sortFields[$name]['label'];
            } else {
                $label = $name;
            }
            $sortOptions[$name] = $label;
        }
        // The sort options are sorted one time only, when saved.

        return $sortOptions;
    }

    /**
     * Order the field by weigth.
     *
     * @param array $fields
     * @param string $settingName
     * @return array
     */
    protected function sortFieldsByWeight(array $fields, $settingName)
    {
        $settings = $this->page->settings()[$settingName];
        uksort($fields, function ($a, $b) use ($settings) {
            $aWeight = $settings[$a]['weight'];
            $bWeight = $settings[$b]['weight'];
            return $aWeight - $bWeight;
        });
        return $fields;
    }
}
