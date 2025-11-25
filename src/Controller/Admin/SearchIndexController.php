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
use Search\Form\Admin\SearchIndexAddForm;
use Search\Form\Admin\SearchIndexEditForm;
use Search\Form\Admin\SearchIndexRebuildForm;

class SearchIndexController extends AbstractActionController
{
    public function addAction()
    {
        $form = $this->getForm(SearchIndexAddForm::class);
        $view = new ViewModel;
        $view->setVariable('form', $form);

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $formData = $form->getData();
                $response = $this->api($form)->create('search_indexes', $formData);
                if ($response) {
                    $searchIndex = $response->getContent();
                    $this->messenger()->addSuccess('Search index created.');

                    return $this->redirect()->toUrl($searchIndex->url('edit'));
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }
        return $view;
    }

    public function editAction()
    {
        $id = $this->params('id');

        $searchIndex = $this->api()->read('search_indexes', $id)->getContent();

        $form = $this->getForm(SearchIndexEditForm::class, [
            'search_index_id' => $id,
        ]);
        $form->setData($searchIndex->jsonSerialize());

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $formData = $form->getData();
                unset($formData['o:adapter']);
                $response = $this->api($form)->update('search_indexes', $id, $formData, [], ['isPartial' => true]);
                if ($response) {
                    $this->messenger()->addSuccess('Search index updated');

                    return $this->redirect()->toRoute('admin/search');
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);

        return $view;
    }

    public function rebuildAction()
    {
        $indexId = $this->params('id');

        $form = $this->getForm(SearchIndexRebuildForm::class);

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $data = $form->getData();
                $jobArgs = [
                    'index-id' => $indexId,
                    'clear-index' => $data['clear-index'] ?? 0,
                ];
                $job = $this->jobDispatcher()->dispatch('Search\Job\RebuildIndex', $jobArgs);

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
}
