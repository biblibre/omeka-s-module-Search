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

namespace Search\Controller\Admin;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Omeka\Form\ConfirmForm;
use Search\Form\Admin\SearchPageForm;
use Search\Form\Admin\SearchPageConfigureForm;

class SearchPageController extends AbstractActionController
{
    protected $entityManager;
    protected $searchAdapterManager;
    protected $searchFormAdapterManager;

    public function addAction()
    {
        $form = $this->getForm(SearchPageForm::class);

        $view = new ViewModel;
        $view->setVariable('form', $form);
        if (!$this->checkPostAndValidForm($form)) {
            return $view;
        }
        $formData = $form->getData();
        $response = $this->api()->create('search_pages', $formData);

        $this->messenger()->addSuccess('Search page created.');
        $searchPage = $response->getContent();
        return $this->redirect()->toUrl($searchPage->url('configure'));
    }

    public function editAction()
    {
        $id = $this->params('id');
        $page = $this->api()->read('search_pages', $id)->getContent();

        $form = $this->getForm(SearchPageForm::class);
        $form->setData($page->jsonSerialize());
        $view = new ViewModel;
        $view->setVariable('form', $form);

        if (!$this->checkPostAndValidForm($form)) {
            return $view;
        }

        $formData = $form->getData();
        $this->api()->update('search_pages', $id, $formData, [], ['isPartial' => true]);

        $this->messenger()->addSuccess('Search page updated.');
        return $this->redirect()->toUrl($page->url('configure'));
    }

    public function configureAction()
    {
        $entityManager = $this->getEntityManager();
        $adapterManager = $this->getSearchAdapterManager();
        $formAdapterManager = $this->getSearchFormAdapterManager();

        $id = $this->params('id');

        $searchPage = $this->api()->read('search_pages', $id)->getContent();
        $adapter = $searchPage->index()->adapter();

        $form = $this->getForm(SearchPageConfigureForm::class, [
            'search_page' => $searchPage,
        ]);
        $form->setData($searchPage->settings());
        $view = new ViewModel;
        $view->setVariable('form', $form);

        if (!$this->checkPostAndValidForm($form)) {
            return $view;
        }

        $formData = $form->getData();
        unset($formData['csrf']);

        $page = $searchPage->getEntity();
        $page->setSettings($formData);
        $entityManager->flush();

        $this->messenger()->addSuccess('Configuration saved.');
        return $this->redirect()->toRoute('admin/search');
    }

    public function deleteConfirmAction()
    {
        $id = $this->params('id');
        $response = $this->api()->read('search_pages', $id);
        $page = $response->getContent();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('common/delete-confirm-details');
        $view->setVariable('resourceLabel', 'search page');
        $view->setVariable('resource', $page);
        return $view;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ConfirmForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api()->delete('search_pages', $this->params('id'));
                $this->messenger()->addSuccess('Search page successfully deleted');
            } else {
                $this->messenger()->addError('Search page could not be deleted');
            }
        }
        return $this->redirect()->toRoute('admin/search');
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

    public function setSearchFormAdapterManager($searchFormAdapterManager)
    {
        $this->searchFormAdapterManager = $searchFormAdapterManager;
    }

    public function getSearchFormAdapterManager()
    {
        return $this->searchFormAdapterManager;
    }

    protected function checkPostAndValidForm($form)
    {
        if (!$this->getRequest()->isPost()) {
            return false;
        }

        $form->setData($this->params()->fromPost());
        if (!$form->isValid()) {
            $this->messenger()->addError('There was an error during validation');
            return false;
        }

        return true;
    }
}
