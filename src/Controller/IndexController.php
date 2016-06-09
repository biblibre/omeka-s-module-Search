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

namespace Search\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Search\Form\BasicForm;
use Search\Querier\Exception\QuerierException;
use Zend\Http\Header\SetCookie;


class IndexController extends AbstractActionController
{
    protected $page;
    protected $index;
    const COOKIE_VIEW_TYPE='search_view_type';
    public function searchAction()
    {
        $serviceLocator = $this->getServiceLocator();
        $api = $serviceLocator->get('Omeka\ApiManager');
        $formManager = $this->getServiceLocator()->get('Search\FormManager');

        $this->page = $api->read('search_pages', $this->params('id'))->getContent();
        $index_id = $this->page->index()->id();
        $form = $formManager->get($this->page->form());
        $form->setAttribute('method', 'GET');

        $view = new ViewModel;
        $view->setVariable('form', $form);

        $params = $this->params()->fromQuery();
        if (!empty($params)) {
            $form->setData($params);
            if ($form->isValid()) {
                $query = $form->toQuery();
                $this->index = $api->read('search_indexes', $index_id)->getContent();

                $querier = $this->index->querier();
                $querier->setServiceLocator($serviceLocator);
                $querier->setLogger($serviceLocator->get('Omeka\Logger'));
                $querier->setIndex($this->index);

                $indexSettings = $this->index->settings();
                $query->setResources($indexSettings['resources']);

                $settings = $this->page->settings();
                foreach ($settings['facets'] as $name => $facet) {
                    if ($facet['enabled']) {
                        $query->addFacetField($name);
                    }
                }

                if (isset($params['limit'])) {
                    foreach ($params['limit'] as $name => $values) {
                        foreach ($values as $value) {
                            $query->addFilter($name, $value);
                        }
                    }
                }

                $sortOptions = $this->getSortOptions();

                if (isset($params['sort'])) {
                    $sort = $params['sort'];
                } else {
                    reset($sortOptions);
                    $sort = key($sortOptions);
                }

                $query->setSort($sort);
                $page_number=isset($params['page'])? $params['page'] : 1;
                $this->setPagination($query,$page_number);
                try {
                    $response = $querier->query($query);
                } catch (QuerierException $e) {
                    $this->messenger()->addError('Query error: ' . $e->getMessage());
                    return $view;
                }

                $facets = $response->getFacetCounts();
                uksort($facets, function($a, $b) use ($settings) {
                    $aWeight = $settings['facets'][$a]['weight'];
                    $bWeight = $settings['facets'][$b]['weight'];
                    return $aWeight - $bWeight;
                });

                $totalResults = array_map(function($resource) use ($response) {
                    return $response->getResourceTotalResults($resource);
                }, $indexSettings['resources']);
                $this->paginator(max($totalResults), $page_number);
                $view->setVariable('view_type',$this->getViewType($params));
                $view->setVariable('query', $query);
                $view->setVariable('response', $response);
                $view->setVariable('facets', $facets);
                $view->setVariable('sortOptions', $sortOptions);
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }

        return $view;
    }

    protected function setPagination($query,$page) {
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $query->setLimitPage($page,$settings->get('pagination_per_page', \Omeka\Service\Paginator::PER_PAGE));
    }


    protected function getCookieViewType() {
        $cookie = $this->getRequest()->getCookie();
        if (isset($cookie[self::COOKIE_VIEW_TYPE]))
            return $cookie[self::COOKIE_VIEW_TYPE];
        return false;
    }

    protected function storeCookieViewType($type) {
        $cookie = new SetCookie(self::COOKIE_VIEW_TYPE,$type);
        $this->getResponse()->getHeaders()
             ->addHeader($cookie);
    }

    protected function getViewType($params) {
        if (($view_type=$this->getCookieViewType()) && !isset($params['view']))
            return $view_type;

        if (!isset($params['view']))
            return 'list';

        if ($params['view']=='list' || $params['view'] == 'grid') {
            $this->storeCookieViewType($params['view']);
            return $params['view'];
        }
        return 'list';

    }


    protected function getSortOptions() {
        $sortOptions = [];

        $sortFields = $this->index->adapter()->getAvailableSortFields();
        $settings = $this->page->settings();
        foreach ($settings['sort_fields'] as $name => $sort_field) {
            if ($sort_field['enabled']) {
                $sortOptions[$name] = $sortFields[$name]['label'];
            }
        }
        uksort($sortOptions, function($a, $b) use ($settings) {
            $aWeight = $settings['sort_fields'][$a]['weight'];
            $bWeight = $settings['sort_fields'][$b]['weight'];
            return $aWeight - $bWeight;
        });

        return $sortOptions;
    }
}
