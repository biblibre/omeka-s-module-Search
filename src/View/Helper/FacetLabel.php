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

namespace Search\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Laminas\Mvc\Application;
use Omeka\Api\Manager as ApiManager;

class FacetLabel extends AbstractHelper
{
    protected $application;
    protected $api;

    protected $availableFacetFields;
    protected $searchPage;

    public function __construct(Application $application, ApiManager $api)
    {
        $this->application = $application;
        $this->api = $api;
    }

    public function __invoke($name)
    {
        $searchPage = $this->getSearchPage();
        $settings = $searchPage->settings();

        if (!empty($settings['facets'][$name]['display']['label'])) {
            return $settings['facets'][$name]['display']['label'];
        }

        $availableFacetFields = $this->getAvailableFacetFields();
        if (!empty($availableFacetFields[$name]['label'])) {
            return $availableFacetFields[$name]['label'];
        }

        return $name;
    }

    protected function getAvailableFacetFields()
    {
        if (!isset($this->availableFacetFields)) {
            $searchPage = $this->getSearchPage();
            $searchAdapter = $searchPage->index()->adapter();

            $this->availableFacetFields = $searchAdapter->getAvailableFacetFields($searchPage->index());
        }

        return $this->availableFacetFields;
    }

    protected function getSearchPage()
    {
        if (!isset($this->searchPage)) {
            $mvcEvent = $this->application->getMvcEvent();
            $routeMatch = $mvcEvent->getRouteMatch();

            $response = $this->api->read('search_pages', $routeMatch->getParam('id'));
            $this->searchPage = $response->getContent();
        }

        return $this->searchPage;
    }
}
