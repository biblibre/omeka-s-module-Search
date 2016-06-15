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
use Search\Form\Admin\SearchIndexForm;
use Search\Form\Admin\SearchIndexConfigureForm;

class SearchIndexController extends AbstractActionController
{
    public function addAction()
    {
        $serviceLocator = $this->getServiceLocator();

        $form = $this->getForm(SearchIndexForm::class);

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $formData = $form->getData();
                $response = $this->api()->create('search_indexes', $formData);
                if ($response->isError()) {
                    $form->setMessages($response->getErrors());
                } else {
                    $this->messenger()->addSuccess('Search index created.');
                    return $this->redirect()->toUrl($response->getContent()->url('configure'));
                }
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }

    public function configureAction()
    {
        $serviceLocator = $this->getServiceLocator();
        $entityManager = $serviceLocator->get('Omeka\EntityManager');
        $adapterManager = $serviceLocator->get('Search\AdapterManager');

        $id = $this->params('id');

        $searchIndex = $entityManager->find('Search\Entity\SearchIndex', $id);
        $adapter = $adapterManager->get($searchIndex->getAdapter());

        $form = $this->getForm(SearchIndexConfigureForm::class);
        $adapterFieldset = $adapter->getConfigFieldset();
        $adapterFieldset->setName('adapter');
        $adapterFieldset->setLabel('Adapter settings');
        $form->add($adapterFieldset);
        $form->setData($searchIndex->getSettings());

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $formData = $form->getData();
                unset($formData['csrf']);
                $searchIndex->setSettings($formData);
                $entityManager->flush();
                $this->messenger()->addSuccess('Search index successfully configured');
                return $this->redirect()->toRoute('admin/search', ['action' => 'browse'], true);
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }

    public function indexAction()
    {
        $serviceLocator = $this->getServiceLocator();
        $jobDispatcher = $serviceLocator->get('Omeka\JobDispatcher');
        $indexId = $this->params('id');

        $job = $jobDispatcher->dispatch('Search\Job\Index', ['index-id' => $indexId]);
        $this->messenger()->addSuccess('Indexing in job ID ' . $job->getId());
        return $this->redirect()->toRoute('admin/search', ['action' => 'browse'], true);
    }

    public function deleteConfirmAction()
    {
        $response = $this->api()->read('search_indexes', $this->params('id'));
        $index = $response->getContent();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('common/delete-confirm-details');
        $view->setVariable('resourceLabel', 'search index');
        $view->setVariable('resource', $index);
        return $view;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ConfirmForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api()->delete('search_indexes', $this->params('id'));
                if ($response->isError()) {
                    $this->messenger()->addError('Search index could not be deleted');
                } else {
                    $this->messenger()->addSuccess('Search index successfully deleted');
                }
            } else {
                $this->messenger()->addError('Search index could not be deleted');
            }
        }
        return $this->redirect()->toRoute('admin/search');
    }
}
