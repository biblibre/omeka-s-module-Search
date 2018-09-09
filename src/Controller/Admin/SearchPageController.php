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

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
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
        $searchPage = $response->getContent();

        $this->messenger()->addSuccess('Search page created.'); // @translate

        $this->managePageOnSites(
            $searchPage->id(),
            !empty($formData['manage_page_default']),
            $formData['manage_page_availability']
        );
        if (!in_array($formData['manage_page_availability'], ['disable', 'enable'])
            && empty($formData['manage_page_default'])
        ) {
            $this->messenger()->addWarning('You can enable this page in your site settings or in admin settings.'); // @translate
        }

        return $this->redirect()->toUrl($searchPage->url('configure'));
    }

    public function editAction()
    {
        $id = $this->params('id');
        $page = $this->api()->read('search_pages', ['id' => $id])->getContent();

        $form = $this->getForm(SearchPageForm::class);
        $form->setData($page->jsonSerialize());
        $view = new ViewModel;
        $view->setVariable('form', $form);

        if (!$this->checkPostAndValidForm($form)) {
            return $view;
        }

        $formData = $form->getData();
        $this->api()->update('search_pages', $id, $formData, [], ['isPartial' => true]);

        $this->messenger()->addSuccess('Search page saved.'); // @translate

        $this->managePageOnSites(
            $id,
            !empty($formData['manage_page_default']),
            $formData['manage_page_availability']
        );

        return $this->redirect()->toRoute('admin/search');
    }

    public function configureAction()
    {
        $entityManager = $this->getEntityManager();

        $id = $this->params('id');
        $searchPage = $this->api()->read('search_pages', $id)->getContent();

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

        $this->messenger()->addSuccess('Configuration saved.'); // @translate
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
                $this->api()->delete('search_pages', $this->params('id'));
                $this->messenger()->addSuccess('Search page successfully deleted'); // @translate
            } else {
                $this->messenger()->addError('Search page could not be deleted'); // @translate
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
        if ($form->isValid()) {
            return true;
        }

        $messages = $form->getMessages();
        if (isset($messages['csrf'])) {
            $this->messenger()->addError('Invalid or missing CSRF token'); // @translate
        } else {
            $this->messenger()->addError('There was an error during validation'); // @translate
        }
        return false;
    }

    /**
     * Config the page for all sites.
     *
     * @param int $searchPageId
     * @param bool $default
     * @param string $availability
     */
    protected function managePageOnSites($searchPageId, $default, $availability)
    {
        if ($default) {
            $availability = 'enable';
            $message = 'The page has been set by default in all sites.'; // @translate
            $this->messenger()->addSuccess($message);
        }

        switch ($availability) {
            case 'disable':
                $available = false;
                $message = 'The page has been disabled in all sites.'; // @translate
                break;
            case 'enable':
                $available = true;
                $message = 'The page has been enabled in all sites.'; // @translate
                break;
            default:
                return;
        }

        $siteSettings = $this->siteSettings();
        $sites = $this->api()->search('sites')->getContent();
        foreach ($sites as $site) {
            $siteSettings->setTargetId($site->id());
            $searchPages = $siteSettings->get('search_pages');
            if ($default) {
                $siteSettings->set('search_main_page', $searchPageId);
            }
            if ($available) {
                $searchPages[] = $searchPageId;
            } else {
                if (($key = array_search($searchPageId, $searchPages)) !== false) {
                    unset($searchPages[$key]);
                }
                if ($siteSettings->get('search_main_page') == $searchPageId) {
                    $siteSettings->set('search_main_page', null);
                }
            }
            $siteSettings->set('search_pages', $searchPages);
        }

        $this->messenger()->addSuccess($message);
    }
}
