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
use Search\Form\Admin\SearchPageConfigureSimpleForm;
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
            $formData['manage_page_default'] ?: [],
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
            $formData['manage_page_default'] ?: [],
            $formData['manage_page_availability']
        );

        return $this->redirect()->toRoute('admin/search');
    }

    public function configureAction()
    {
        $entityManager = $this->getEntityManager();

        $id = $this->params('id');

        $view = new ViewModel;

        /** @var \Search\Api\Representation\SearchPageRepresentation $searchPage */
        $searchPage = $this->api()->read('search_pages', $id)->getContent();
        $view->setVariable('searchPage', $searchPage);

        $index = $searchPage->index();
        $adapter = $index ? $index->adapter() : null;
        if (empty($adapter)) {
            $message = new Message(
                'The index adapter "%s" is unavailable', // @translate
                $index->adapterLabel()
            );
            $this->messenger()->addError($message); // @translate
            return $view;
        }

        $form = $this->getConfigureForm($searchPage);
        $isSimple = $form instanceof SearchPageConfigureSimpleForm;

        $settings = $searchPage->settings() ?: [];

        if ($isSimple) {
            $settings = $this->prepareSettingsForSimpleForm($searchPage, $settings);
        }
        $form->setData($settings);
        $view->setVariable('form', $form);

        if (!$this->getRequest()->isPost()) {
            return $view;
        }

        $params = $isSimple
            ? $this->extractSimpleFields()
            : $this->extractFullFields();

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

        // TODO Why the fieldset "form" is removed from the params? Add an intermediate fieldset?
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

        // Check if the name of the path is single in the database.
        $params = $this->params()->fromPost();
        $id = $this->params('id');
        $path = $params['o:path'];

        $paths = $this->api()
            ->search('search_pages', [], ['returnScalar' => 'path'])
            ->getContent();
        if (in_array($path, $paths)) {
            if (!$id) {
                $this->messenger()->addError('The path should be unique.'); // @translate
                return false;
            }
            $searchPageId = $this->api()
                ->searchOne('search_pages', ['path' => $path], ['returnScalar' => 'id'])
                ->getContent();
            if ($id !== $searchPageId) {
                $this->messenger()->addError('The path should be unique.'); // @translate
                return false;
            }
        }

        $form->setData($params);
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
     * Check if the configuration should use simple or visual form and get it.
     *
     * The form is different when the number of fields is too big. This is
     * generally needed for the internal adapter when there are many specific
     * vocabularies. Unlike other adapters, it uses all properties by default.
     * So the number of properties may be greater than 200, so a memory overload
     * may occur (memory_limit = 128MB).
     * For the full form, the issue about the limit for the for number of fields
     * by request (max_input_vars = 1000) is fixed via js. Each property has 3
     * fields, and as facet and sort in 2 directions, so the limit to use the
     * full form or the simple form is set to 200.
     *
     * @param SearchPageRepresentation $searchPage
     * @return \Search\Form\Admin\SearchPageConfigureForm|\Search\Form\Admin\SearchPageConfigureSimpleForm
     */
    protected function getConfigureForm(SearchPageRepresentation $searchPage)
    {
        $index = $searchPage->index();
        $adapter = $index ? $index->adapter() : null;
        $availableFields = $adapter->getAvailableFields($index);

        $isPostSimple = $this->getRequest()->isPost()
            && $this->params()->fromPost('form_class') === SearchPageConfigureSimpleForm::class;
        $forceForm = $this->params()->fromQuery('form');
        $isSimple = $isPostSimple
            || (count($availableFields) > 200 && $forceForm !== 'visual')
            || $forceForm === 'simple';

        $form = $isSimple
            /* @var \Search\Form\Admin\SearchPageConfigureSimpleForm $form */
            ? $this->getForm(SearchPageConfigureSimpleForm::class, ['search_page' => $searchPage])
            /* @var \Search\Form\Admin\SearchPageConfigureForm $form */
            : $this->getForm(SearchPageConfigureForm::class, ['search_page' => $searchPage]);

        return $form;
    }

    /**
     * Convert settings into strings in ordeer to manage many fields.
     *
     * @param SearchPageRepresentation $searchPage
     * @param array $settings
     * @return array
     */
    protected function prepareSettingsForSimpleForm(SearchPageRepresentation $searchPage, $settings)
    {
        $index = $searchPage->index();
        $adapter = $index->adapter();

        $data = '';
        $fields = empty($settings['facets']) ? [] : $settings['facets'];
        foreach ($fields as $name => $field) {
            if (!empty($field['enabled'])) {
                $data .= $name . ' | ' . $field['display']['label'] . "\n";
            }
        }
        $settings['facets'] = $data;

        $data = '';
        $fields = $adapter->getAvailableFacetFields($index);
        foreach ($fields as $name => $field) {
            $data .= $name . ' | ' . $field['label'] . "\n";
        }
        $settings['available_facets'] = $data;

        $data = '';
        $fields = empty($settings['sort_fields']) ? [] : $settings['sort_fields'];
        foreach ($fields as $name => $field) {
            if (!empty($field['enabled'])) {
                $data .= $name . ' | ' . $field['display']['label'] . "\n";
            }
        }
        $settings['sort_fields'] = $data;

        $data = '';
        $fields = $adapter->getAvailableSortFields($index);
        foreach ($fields as $name => $field) {
            $data .= $name . ' | ' . $field['label'] . "\n";
        }
        $settings['available_sort_fields'] = $data;

        return $settings;
    }

    protected function extractSimpleFields()
    {
        $params = $this->getRequest()->getPost()->toArray();

        $data = $params['facets'] ?: '';
        unset($params['facets']);
        unset($params['available_facets']);
        $data = $this->stringToList($data);
        foreach ($data as $key => $value) {
            list($term, $label) = array_map('trim', explode('|', $value));
            $params['facets'][$term] = [
                'enabled' => true,
                'weight' => $key + 1,
                'display' => [
                    'label' => $label,
                ],
            ];
        }

        $data = $params['sort_fields'] ?: '';
        unset($params['sort_fields']);
        unset($params['available_sort_fields']);
        $data = $this->stringToList($data);
        foreach ($data as $key => $value) {
            list($term, $label) = array_map('trim', explode('|', $value));
            $params['sort_fields'][$term] = [
                'enabled' => true,
                'weight' => $key + 1,
                'display' => [
                    'label' => $label,
                ],
            ];
        }

        unset($params['form_class']);

        return $params;
    }

    protected function extractFullFields()
    {
        $params = $this->getRequest()->getPost()->toArray();

        $fields = isset($params['fieldsets']) ? $params['fieldsets'] : [];
        unset($params['fieldsets']);
        $fieldsData = $this->extractJsonEncodedFields($fields);
        $params = array_merge($fieldsData, $params);
        unset($fields);

        unset($params['form_class']);

        return $params;
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
                $message = 'The availability of pages of sites was let unmodified.'; // @translate
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
                    $searchPages = array_unique(array_filter($searchPages));
                    sort($searchPages);
                    $settings->set('search_pages', $searchPages);
                } else {
                    $settings->set('search_main_page', null);
                }
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

    /**
     * Get each line of a string separately.
     *
     * @param string $string
     * @return array
     */
    protected function stringToList($string)
    {
        return array_filter(array_map('trim', explode("\n", $this->fixEndOfLine($string))));
    }

    /**
     * Clean the text area from end of lines.
     *
     * This method fixes Windows and Apple copy/paste from a textarea input.
     *
     * @param string $string
     * @return string
     */
    protected function fixEndOfLine($string)
    {
        return str_replace(["\r\n", "\n\r", "\r"], ["\n", "\n", "\n"], $string);
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
