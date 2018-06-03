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
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    /**
     * @var SearchPageRepresentation
     */
    protected $page;

    /**
     *
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
        if (!isset($formAdapter)) {
            $formAdapterName = $page->formAdapterName();
            $msg = sprintf('Form adapter "%s" not found', $formAdapterName); // @translate
            throw new RuntimeException($msg);
        }

        // FIXME The form should not require to be initialized with a page to use the view helper.
        /** @var \Zend\Form\Form $form */
        $form = $this->searchForm($page);

        $params = $this->params()->fromQuery();
        if (empty($params)) {
            return $view;
        }

        $form->setData($params);
        if (!$form->isValid()) {
            $this->messenger()->addError('There was an error during validation.'); // @translate
            return $view;
        }

        $searchPageSettings = $page->settings();
        $searchFormSettings = [];
        if (isset($searchPageSettings['form'])) {
            $searchFormSettings = $searchPageSettings['form'];
        }

        $this->index = $page->index();
        $index = $this->index;

        /** @var \Search\Query $query */
        $query = $formAdapter->toQuery($form->getData(), $searchFormSettings);

        $indexSettings = $index->settings();
        if (array_key_exists('resource_type', $params)) {
            $resourceType = $params['resource_type'];
            if (!is_array($resourceType)) {
                $resourceType = [$resourceType];
            }
            $query->setResources($resourceType);
        } else {
            $query->setResources($indexSettings['resources']);
        }

        $settings = $page->settings();
        foreach ($settings['facets'] as $name => $facet) {
            if ($facet['enabled']) {
                $query->addFacetField($name);
            }
        }
        if (isset($settings['facet_limit'])) {
            $query->setFacetLimit($settings['facet_limit']);
        }

        if (isset($params['limit'])) {
            foreach ($params['limit'] as $name => $values) {
                foreach ($values as $value) {
                    $query->addFilter($name, $value);
                }
            }
        }

        if ($site) {
            $query->setSite($site);
        }

        $sortOptions = $this->getSortOptions();

        if (isset($params['sort'])) {
            $sort = $params['sort'];
        } else {
            reset($sortOptions);
            $sort = key($sortOptions);
        }

        $query->setSort($sort);
        $querier = $index->querier();
        $pageNumber = isset($params['page']) ? $params['page'] : 1;
        $this->setPagination($query, $pageNumber);
        try {
            $response = $querier->query($query);
        } catch (QuerierException $e) {
            $this->messenger()->addError(sprintf('Query error: %s', $e->getMessage())); // @translate
            return $view;
        }

        $facets = $response->getFacetCounts();
        $facets = $this->sortByWeight($facets, 'facets');

        $totalResults = array_map(function ($resource) use ($response) {
            return $response->getResourceTotalResults($resource);
        }, $indexSettings['resources']);
        $this->paginator(max($totalResults), $pageNumber);

        $view->setVariable('query', $query);
        $view->setVariable('site', $site);
        $view->setVariable('response', $response);
        $view->setVariable('facets', $facets);
        $view->setVariable('sortOptions', $sortOptions);
        return $view;
    }

    protected function setPagination($query, $page)
    {
        $perPage = $this->settings()->get('pagination_per_page', Paginator::PER_PAGE);
        $query->setLimitPage($page, $perPage);
    }

    protected function sortByWeight($fields, $settingName)
    {
        $settings = $this->page->settings();
        uksort($fields, function ($a, $b) use ($settings, $settingName) {
            $aWeight = $settings[$settingName][$a]['weight'];
            $bWeight = $settings[$settingName][$b]['weight'];
            return $aWeight - $bWeight;
        });
        return $fields;
    }

    protected function getSortOptions()
    {
        $sortOptions = [];

        $sortFields = $this->index->adapter()->getAvailableSortFields($this->index);
        $settings = $this->page->settings();
        foreach ($settings['sort_fields'] as $name => $sortField) {
            if ($sortField['enabled']) {
                if (!empty($sortField['display']['label'])) {
                    $label = $sortField['display']['label'];
                } elseif (!empty($sortFields[$name]['label'])) {
                    $label = $sortFields[$name]['label'];
                } else {
                    $label = $name;
                }

                $sortOptions[$name] = $label;
            }
        }
        $sortOptions = $this->sortByWeight($sortOptions, 'sort_fields');

        return $sortOptions;
    }
}
