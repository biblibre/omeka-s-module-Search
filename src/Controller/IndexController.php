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

class IndexController extends AbstractActionController
{
    protected $page;
    protected $index;

    public function searchAction()
    {
        $serviceLocator = $this->getServiceLocator();
        $api = $serviceLocator->get('Omeka\ApiManager');
        $form = new BasicForm($serviceLocator);
        $form->setAttribute('method', 'GET');

        $this->page = $api->read('search_pages', $this->params('id'))->getContent();
        $index_id = $this->page->index()->id();
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
