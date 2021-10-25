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

class SavedQueryController extends AbstractActionController
{
    protected $entityManager;
    protected $searchAdapterManager;
    protected $searchPageUrl;

    public function saveAction()
    {
        $siteId = $this->params()->fromPost('site_id');
        $searchPageId = $this->params()->fromPost('search_page_id');
        $queryString = $this->params()->fromPost('query_string');
        $queryTitle = $this->params()->fromPost('query_title');
        $queryDescription = $this->params()->fromPost('query_description');

        $this->api()->create('saved_queries', [
            'o:site_id' => $siteId,
            'o:search_page_id' => $searchPageId,
            'o:query_string' => $queryString,
            'o:query_title' => $queryTitle,
            'o:query_description' => $queryDescription,
        ]);

        return $this->redirect()->toUrl($this->url()->fromRoute('search-page-' . $searchPageId, [], ['query' => json_decode($queryString, true)], true));
    }

    public function deleteAction()
    {
        $queryId = $this->params()->fromPost('query_id');
        $currentUrl = $this->params()->fromPost('current_url');

        $this->api()->delete('saved_queries', $queryId);

        return $this->redirect()->toUrl($currentUrl);
    }

    public function setEntityManager($entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getEntityManager()
    {
        return $this->entityManager;
    }

    public function setSearchAdapterManager($searchAdapterManager)
    {
        $this->searchAdapterManager = $searchAdapterManager;
    }

    public function getSearchAdapterManager()
    {
        return $this->searchAdapterManager;
    }
}
