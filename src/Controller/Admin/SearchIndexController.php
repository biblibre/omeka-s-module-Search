<?php

/*
 * Copyright BibLibre, 2016-2021
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
use Omeka\Stdlib\Message;
use Search\Form\Admin\SearchIndexForm;
use Search\Form\Admin\SearchIndexConfigureForm;
use Search\Form\Admin\SearchIndexRebuildForm;

class SearchIndexController extends AbstractActionController
{
    protected $entityManager;
    protected $searchAdapterManager;
    protected $jobDispatcher;

    public function addAction()
    {
        $form = $this->getForm(SearchIndexForm::class);
        $view = new ViewModel;
        $view->setVariable('form', $form);

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if (!$form->isValid()) {
                $this->messenger()->addError('There was an error during validation');
                return $view;
            }
            $formData = $form->getData();
            $response = $this->api()->create('search_indexes', $formData);
            $this->messenger()->addSuccess('Search index created.');
            return $this->redirect()->toUrl($response->getContent()->url('configure'));
        }
        return $view;
    }

    public function configureAction()
    {
        $entityManager = $this->getEntityManager();
        $adapterManager = $this->getSearchAdapterManager();

        $id = $this->params('id');

        $searchIndex = $entityManager->find('Search\Entity\SearchIndex', $id);
        $adapter = $adapterManager->get($searchIndex->getAdapter());

        $form = $this->getForm(SearchIndexConfigureForm::class, [
            'search_index_id' => $id,
        ]);
        $adapterFieldset = $adapter->getConfigFieldset();
        $adapterFieldset->setName('adapter');
        $adapterFieldset->setLabel('Adapter settings');
        $form->add($adapterFieldset);
        $form->setData($searchIndex->getSettings());

        $view = new ViewModel;
        $view->setVariable('form', $form);

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if (!$form->isValid()) {
                $this->messenger()->addError('There was an error during validation');
                return $view;
            }
            $formData = $form->getData();
            unset($formData['csrf']);
            $searchIndex->setSettings($formData);
            $entityManager->flush();
            $this->messenger()->addSuccess('Search index successfully configured');
            return $this->redirect()->toRoute('admin/search', ['action' => 'browse'], true);
        }

        return $view;
    }

    public function rebuildAction()
    {
        $jobDispatcher = $this->getJobDispatcher();
        $indexId = $this->params('id');

        $form = $this->getForm(SearchIndexRebuildForm::class);

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $data = $form->getData();
                $jobArgs = [
                    'index-id' => $indexId,
                    'clear-index' => $data['clear-index'] ?? 0,
                    'batch-size' => $data['batch-size'],
                ];
                $job = $jobDispatcher->dispatch('Search\Job\Index', $jobArgs);

                $jobUrl = $this->url()->fromRoute('admin/id', [
                    'controller' => 'job',
                    'action' => 'show',
                    'id' => $job->getId(),
                ]);

                $message = new Message(
                    'Index rebuilding started in %sjob %s%s', // @translate
                    sprintf('<a href="%s">', htmlspecialchars($jobUrl)),
                    $job->getId(),
                    '</a>'
                );

                $message->setEscapeHtml(false);
                $this->messenger()->addSuccess($message);

                return $this->redirect()->toRoute('admin/search', ['action' => 'browse'], true);
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);

        return $view;
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
                $this->messenger()->addSuccess('Search index successfully deleted');
            } else {
                $this->messenger()->addError('Search index could not be deleted');
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

    public function setJobDispatcher($jobDispatcher)
    {
        $this->jobDispatcher = $jobDispatcher;
    }

    public function getJobDispatcher()
    {
        return $this->jobDispatcher;
    }
}
