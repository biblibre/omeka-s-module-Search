<?php

/*
 * Copyright BibLibre, 2016-2017
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

use Doctrine\ORM\EntityManager;
use Omeka\Form\ConfirmForm;
use Omeka\Stdlib\Message;
use Search\Adapter\Manager as SearchAdapterManager;
use Search\Api\Representation\SearchPageRepresentation;
use Search\Form\Admin\SearchPageForm;
use Search\Form\Admin\SearchPageConfigureForm;
use Search\FormAdapter\Manager as SearchFormAdapterManager;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class SearchPageController extends AbstractActionController
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var SearchAdapterManager
     */
    protected $searchAdapterManager;

    /**
     * @var SearchFormAdapterManager
     */
    protected $searchFormAdapterManager;

    /**
     * @param EntityManager $entityManager
     * @param SearchAdapterManager $searchAdapterManager
     * @param SearchFormAdapterManager $searchFormAdapterManager
     */
    public function __construct(
        EntityManager $entityManager,
        SearchAdapterManager $searchAdapterManager,
        SearchFormAdapterManager $searchFormAdapterManager
    ) {
        $this->entityManager = $entityManager;
        $this->searchAdapterManager = $searchAdapterManager;
        $this->searchFormAdapterManager = $searchFormAdapterManager;
    }

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

        $this->messenger()->addSuccess(new Message(
            'Search page "%s" created.', // @translate
            $searchPage->name()
        ));
        $this->manageSearchPageOnSites(
            $searchPage,
            $formData['manage_page_default'],
            $formData['manage_page_availability']
        );
        if (!in_array($formData['manage_page_availability'], ['disable', 'enable'])
            && empty($formData['manage_page_default'])
        ) {
            $this->messenger()->addWarning('You can enable this page in your site settings or in admin settings.'); // @translate
        }

        if ($searchPage->formAdapter() instanceof \Search\FormAdapter\ApiFormAdapter) {
            $this->messenger()->addWarning(
                'The api adapter should be selected in the main settings.' // @translate
            );
        }

        return $this->redirect()->toUrl($searchPage->url('configure'));
    }

    public function editAction()
    {
        /** @var \Search\Api\Representation\SearchPageRepresentation $page */
        $id = $this->params('id');
        $page = $this->api()->read('search_pages', ['id' => $id])->getContent();

        $data = $page->jsonSerialize();
        $data['manage_page_default'] = $this->sitesWithSearchPage($page);

        $form = $this->getForm(SearchPageForm::class);
        $form->setData($data);

        $view = new ViewModel;
        $view->setVariable('form', $form);

        if (!$this->checkPostAndValidForm($form)) {
            return $view;
        }

        $formData = $form->getData();
        $searchPage = $this->api()
            ->update('search_pages', $id, $formData, [], ['isPartial' => true])
            ->getContent();

        $this->messenger()->addSuccess(new Message(
            'Search page "%s" saved.', // @translate
            $searchPage->name()
        ));

        $this->manageSearchPageOnSites(
            $searchPage,
            $formData['manage_page_default'],
            $formData['manage_page_availability']
        );

        return $this->redirect()->toRoute('admin/search');
    }

    public function configureAction()
    {
        $entityManager = $this->getEntityManager();

        $id = $this->params('id');

        /** @var \Search\Api\Representation\SearchPageRepresentation $searchPage */
        $searchPage = $this->api()->read('search_pages', $id)->getContent();

        $form = $this->getForm(SearchPageConfigureForm::class, [
            'search_page' => $searchPage,
        ]);
        $settings = $searchPage->settings();
        $form->setData($settings);

        $view = new ViewModel;
        $view->setVariable('form', $form);

        if (!$this->getRequest()->isPost()) {
            return $view;
        }

        // Fix the "max_input_vars" limit (default to 1000 in php.ini) via js.
        $params = $this->getRequest()->getPost()->toArray();
        $fields = isset($params['fieldsets']) ? $params['fieldsets'] : [];
        unset($params['fieldsets']);
        $fieldsData = $this->extractJsonEncodedFields($fields);
        $params = array_merge($fieldsData, $params);
        unset($fields);

        $form->setData($params);
        if (!$form->isValid()) {
            $messages = $form->getMessages();
            if (isset($messages['csrf'])) {
                $this->messenger()->addError('Invalid or missing CSRF token'); // @translate
            } else {
                $this->messenger()->addError('There was an error during validation'); // @translate
            }
            return $view;
        }

        // TODO Why the fieldset "form" is removed from the the params? Add an intermediate fieldset?
        $formParams = isset($params['form']) ? $params['form'] : [];
        $params = $form->getData();
        $params['form'] = $formParams;
        unset($params['csrf']);

        // Sort facets and sort fields to simplify next load.
        foreach (['facets', 'sort_fields'] as $type) {
            if (empty($params[$type])) {
                continue;
            }
            // Sort enabled first, then available, else sort by weigth.
            uasort($params[$type], function ($a, $b) {
                // Sort by availability.
                if (isset($a['enabled']) && isset($b['enabled'])) {
                    if ($a['enabled'] > $b['enabled']) {
                        return -1;
                    } elseif ($a['enabled'] < $b['enabled']) {
                        return 1;
                    }
                } elseif (isset($a['enabled'])) {
                    return -1;
                } elseif (isset($b['enabled'])) {
                    return 1;
                }
                // In other cases, sort by weight.
                if (isset($a['weight']) && isset($b['weight'])) {
                    return $a['weight'] == $b['weight']
                        ? 0
                        : ($a['weight'] < $b['weight'] ? -1 : 1);
                } elseif (isset($a['weight'])) {
                    return -1;
                } elseif (isset($b['weight'])) {
                    return 1;
                }
                return 0;
            });
        }

        $page = $searchPage->getEntity();
        $page->setSettings($params);
        $entityManager->flush();

        $this->messenger()->addSuccess(new Message(
            'Configuration saved for page "%s".', // @translate
            $searchPage->name()
        ));

        return $this->redirect()->toRoute('admin/search');
    }

    public function deleteConfirmAction()
    {
        $id = $this->params('id');
        $page = $this->api()->read('search_pages', $id)->getContent();

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
            $id = $this->params('id');
            $pageName = $this->api()->read('search_pages', $id)->getContent()->name();
            if ($form->isValid()) {
                $this->api()->delete('search_pages', $this->params('id'));
                $this->messenger()->addSuccess(new Message(
                    'Search page "%s" successfully deleted', // @translate
                    $pageName
                ));
            } else {
                $this->messenger()->addError(new Message(
                    'Search page "%s" could not be deleted', // @translate
                    $pageName
                ));
            }
        }
        return $this->redirect()->toRoute('admin/search');
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
     * To bypass the limit to 1000 fields posted, post is json encoded, so it
     * should be decoded.
     *
     * @param string $jsonEncodedFields
     * @return array
     */
    protected function extractJsonEncodedFields($jsonEncodedFields)
    {
        if (empty($jsonEncodedFields)) {
            return [];
        }
        $fields = json_decode($jsonEncodedFields, true);
        if (empty($jsonEncodedFields)) {
            return [];
        }

        // Recreate the array that was json encoded via js.
        $fieldsData = [];
        foreach ($fields as $type => $typeFields) {
            foreach ($typeFields as $fieldData) {
                $type = strtok($fieldData['name'], '[]');
                $two = strtok('[]');
                if (!strlen($two)) {
                    $fieldsData[$type] = $fieldData['value'];
                } else {
                    $three = strtok('[]');
                    if (!strlen($three)) {
                        $fieldsData[$type][$two] = $fieldData['value'];
                    } else {
                        $four = strtok('[]');
                        if (!strlen($four)) {
                            $fieldsData[$type][$two][$three] = $fieldData['value'];
                        } else {
                            $fieldsData[$type][$two][$three][$four] = $fieldData['value'];
                        }
                    }
                }
            }
        }

        return $fieldsData;
    }

    protected function sitesWithSearchPage(SearchPageRepresentation $searchPage)
    {
        $result = [];

        // Check admin.
        $adminSearchUrl = $this->settings()->get('search_main_page');
        if ($adminSearchUrl) {
            $basePath = $this->viewHelpers()->get('basePath');
            $adminBasePath = $basePath('admin/');
            if ($adminSearchUrl === ($adminBasePath . $searchPage->path())) {
                $result[] = 'admin';
            }
        }

        // Check all sites.
        $searchPageId = $searchPage->id();
        $settings = $this->siteSettings();
        $sites = $this->api()->search('sites')->getContent();
        foreach ($sites as $site) {
            $settings->setTargetId($site->id());
            if ($settings->get('search_main_page') == $searchPageId) {
                $result[] = $site->id();
            }
        }

        return $result;
    }

    /**
     * Config the page for all sites.
     *
     * @param SearchPageRepresentation $searchPage
     * @param array $mainSearchPageForSites
     * @param string $availability
     */
    protected function manageSearchPageOnSites(
        SearchPageRepresentation $searchPage,
        array $newMainSearchPageForSites,
        $availability
    ) {
        $searchPageId = $searchPage->id();
        $currentMainSearchPageForSites = $this->sitesWithSearchPage($searchPage);

        // Manage admin settings.
        $current = in_array('admin', $currentMainSearchPageForSites);
        $new = in_array('admin', $newMainSearchPageForSites);
        if ($current !== $new) {
            $settings = $this->settings();
            if ($new) {
                $basePath = $this->viewHelpers()->get('basePath');
                $adminBasePath = $basePath('admin/');
                $settings->set('search_main_page', $adminBasePath . $searchPage->path());

                $searchPages = $settings->get('search_pages', []);
                $searchPages[] = $searchPageId;
                array_unique(array_filter($searchPages));
                sort($searchPages);
                $settings->set('search_pages', $searchPages);

                $message = 'The page has been set by default in admin board.'; // @translate
            } else {
                $settings->set('search_main_page', null);

                $message = 'The page has been unset in admin board.'; // @translate
            }
            $this->messenger()->addSuccess($message);
        }

        $allSites = in_array('all', $newMainSearchPageForSites);
        switch ($availability) {
            case 'disable':
                $available = false;
                $message = 'The page has been disabled in all specified sites.'; // @translate
                break;
            case 'enable':
                $available = true;
                $message = 'The page has been made available in all specified sites.'; // @translate
                break;
            default:
                $available = null;
        }

        // Manage site settings.
        $settings = $this->siteSettings();
        $sites = $this->api()->search('sites')->getContent();
        foreach ($sites as $site) {
            $siteId = $site->id();
            $settings->setTargetId($siteId);
            $searchPages = $settings->get('search_pages', []);
            $current = in_array($siteId, $currentMainSearchPageForSites);
            $new = $allSites || in_array($siteId, $newMainSearchPageForSites);
            if ($current !== $new) {
                if ($new) {
                    $settings->set('search_main_page', $siteId);

                    $searchPages[] = $searchPageId;
                    array_unique(array_filter($searchPages));
                    sort($searchPages);
                    $settings->set('search_pages', $searchPages);
                } else {
                    $settings->set('search_main_page', null);
                }
                $this->messenger()->addSuccess($message);
            }

            if ($new || $available) {
                $searchPages[] = $searchPageId;
                array_unique(array_filter($searchPages));
                sort($searchPages);
            } else {
                $key = array_search($searchPageId, $searchPages);
                if ($key === false) {
                    continue;
                }
                unset($searchPages[$key]);
            }
            $settings->set('search_pages', $searchPages);
        }

        $this->messenger()->addSuccess($message);
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    protected function getSearchAdapterManager()
    {
        return $this->searchAdapterManager;
    }

    protected function getSearchFormAdapterManager()
    {
        return $this->searchFormAdapterManager;
    }
}
