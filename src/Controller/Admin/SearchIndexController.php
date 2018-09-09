<?php

/*
 * Copyright BibLibre, 2016
 * Copyright Daniel Berthereau, 2018
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

use Omeka\Form\ConfirmForm;
use Omeka\Stdlib\Message;
use Search\Form\Admin\SearchIndexForm;
use Search\Form\Admin\SearchIndexConfigureForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

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
                $this->messenger()->addError('There was an error during validation'); // @translate
                return $view;
            }
            $formData = $form->getData();
            $index = $this->api()->create('search_indexes', $formData)->getContent();
            $this->messenger()->addSuccess(new Message(
                'Search index "%s" created.', // @translate
                $index->name()
            ));
            return $this->redirect()->toUrl($index->url('edit'));
        }
        return $view;
    }

    public function editAction()
    {
        $entityManager = $this->getEntityManager();
        $adapterManager = $this->getSearchAdapterManager();

        $id = $this->params('id');

        $searchIndex = $entityManager->find(\Search\Entity\SearchIndex::class, $id);
        $adapter = $adapterManager->get($searchIndex->getAdapter());

        $form = $this->getForm(SearchIndexConfigureForm::class, [
            'search_index_id' => $id,
        ]);
        $adapterFieldset = $adapter->getConfigFieldset();
        if ($adapterFieldset) {
            $adapterFieldset->setName('adapter');
            $adapterFieldset->setLabel('Adapter settings'); // @translate
            $form->add($adapterFieldset);
        }
        $form->setData($searchIndex->getSettings());

        $view = new ViewModel;
        $view->setVariable('form', $form);

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if (!$form->isValid()) {
                $this->messenger()->addError('There was an error during validation'); // @translate
                return $view;
            }
            $formData = $form->getData();
            unset($formData['csrf']);
            $searchIndex->setSettings($formData);
            $entityManager->flush();
            $this->messenger()->addSuccess(new Message(
                'Search index "%s" successfully configured.',  // @translate
                $searchIndex->getName()
            ));
            return $this->redirect()->toRoute('admin/search', ['action' => 'browse'], true);
        }

        return $view;
    }

    public function indexConfirmAction()
    {
        $index = $this->api()->read('search_indexes', $this->params('id'))->getContent();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('search/admin/search-index/index-confirm-details');
        $view->setVariable('resourceLabel', 'search index');
        $view->setVariable('resource', $index);
        return $view;
    }

    public function indexAction()
    {
        $searchIndexId = (int) $this->params('id');
        $index = $this->api()->read('search_indexes', $searchIndexId)->getContent();

        $startResourceId = (int) $this->params()->fromPost('start_resource_id');
        $resourceNames = $this->params()->fromPost('resource_names') ?: [];

        $jobArgs = [];
        $jobArgs['search_index_id'] = $searchIndexId;
        $jobArgs['start_resource_id'] = $startResourceId;
        $jobArgs['resource_names'] = $resourceNames;
        $jobDispatcher = $this->getJobDispatcher();
        $job = $jobDispatcher->dispatch(\Search\Job\SearchIndex::class, $jobArgs);

        $jobUrl = $this->url()->fromRoute('admin/id', [
            'controller' => 'job',
            'action' => 'show',
            'id' => $job->getId(),
        ]);

        $message = new Message(
            'Indexing of "%s" started in %sjob %s%s', // @translate
            $index->name(),
            sprintf('<a href="%s">', htmlspecialchars($jobUrl)),
            $job->getId(),
            '</a>'
        );

        $message->setEscapeHtml(false);
        $this->messenger()->addSuccess($message);

        return $this->redirect()->toRoute('admin/search', ['action' => 'browse'], true);
    }

    public function deleteConfirmAction()
    {
        $response = $this->api()->read('search_indexes', $this->params('id'));
        $index = $response->getContent();

        // TODO Add a warning about the related pages, that will be deleted.
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
            $indexId = $this->params('id');
            $indexName = $this->api()->read('search_indexes', $indexId)->getContent()->name();
            if ($form->isValid()) {
                $this->api()->delete('search_indexes', $indexId);
                $this->messenger()->addSuccess(new Message(
                    'Search index "%s" successfully deleted', // @translate
                    $indexName
                ));
            } else {
                $this->messenger()->addError(new Message(
                    'Search index "%s" could not be deleted', // @translate
                    $indexName
                ));
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
