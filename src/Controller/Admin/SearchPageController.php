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

namespace Search\Controller\Admin;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Omeka\Form\ConfirmForm;
use Search\Form\Admin\SearchPageForm;
use Search\Form\Admin\SearchPageConfigureForm;

class SearchPageController extends AbstractActionController
{
    public function addAction()
    {
        $serviceLocator = $this->getServiceLocator();

        $form = $this->getForm(SearchPageForm::class);

        $view = new ViewModel;
        $view->setVariable('form', $form);
        if (!$this->checkPostAndValidForm($form))
            return $view;
        $formData = $form->getData();
        $response = $this->api()->create('search_pages', $formData);
        if ($response->isError()) {
            $form->setMessages($response->getErrors());
            return $view;
        }

        $this->messenger()->addSuccess('Search page created.');
        return $this->redirect()->toRoute('admin/search');

    }

    protected function checkPostAndValidForm($form) {
        if (!$this->getRequest()->isPost())
            return false;

        $form->setData($this->params()->fromPost());
        if (!$form->isValid()) {
            $this->messenger()->addError('There was an error during validation');
            return false;
        }
        return true;
    }

    public function editAction()
    {
        $serviceLocator = $this->getServiceLocator();
        $api = $serviceLocator->get('Omeka\ApiManager');

        $id = $this->params('id');
        $page = $api->read('search_pages', $id)->getContent();

        $form = $this->getForm(SearchPageForm::class);
        $form->setData($page->jsonSerialize());
        $view = new ViewModel;
        $view->setVariable('form', $form);

        if (!$this->checkPostAndValidForm($form))
            return $view;

        $formData = $form->getData();
        $response = $this->api()->update('search_pages', $id, $formData, [], true);
        if ($response->isError()) {
            $form->setMessages($response->getErrors());
            return $view;
        }

        $this->messenger()->addSuccess('Search page created.');
        return $this->redirect()->toRoute('admin/search');

    }

    public function configureAction()
    {
        $serviceLocator = $this->getServiceLocator();
        $api = $serviceLocator->get('Omeka\ApiManager');
        $entityManager = $serviceLocator->get('Omeka\EntityManager');
        $adapterManager = $serviceLocator->get('Search\AdapterManager');
        $formAdapterManager = $serviceLocator->get('Search\FormAdapterManager');

        $id = $this->params('id');

        $searchPage = $api->read('search_pages', $id)->getContent();
        $adapter = $searchPage->index()->adapter();

        $form = $this->getForm(SearchPageConfigureForm::class, [
            'search_page' => $searchPage,
        ]);
        $form->setData($searchPage->settings());
        $view = new ViewModel;
        $view->setVariable('form', $form);

        if (!$this->checkPostAndValidForm($form))
            return $view;

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
        $serviceLocator = $this->getServiceLocator();
        $api = $serviceLocator->get('Omeka\ApiManager');
        $id = $this->params('id');
        $response = $api->read('search_pages', $id);
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
                if ($response->isError()) {
                    $this->messenger()->addError('Search page could not be deleted');
                } else {
                    $this->messenger()->addSuccess('Search page successfully deleted');
                }
            } else {
                $this->messenger()->addError('Search page could not be deleted');
            }
        }
        return $this->redirect()->toRoute('admin/search');
    }
}
