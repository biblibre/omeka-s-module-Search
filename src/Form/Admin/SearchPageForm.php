<?php

/*
 * Copyright BibLibre, 2016-2017
 * Copyright Daniel Berthereau 2017-2018
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

namespace Search\Form\Admin;

use Omeka\Api\Manager as ApiManager;
use Omeka\Form\Element\SiteSelect;
use Zend\Form\Element;
use Zend\Form\Form;
use Zend\I18n\Translator\TranslatorAwareTrait;

class SearchPageForm extends Form
{
    use TranslatorAwareTrait;

    /**
     * @var ApiManager
     */
    protected $apiManager;

    protected $formAdapterManager;

    public function init()
    {
        $translator = $this->getTranslator();

        $this->add([
            'name' => 'o:name',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Name', // @translate
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'o:path',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Path', // @translate
                'info' => $translator->translate('The path to the search form.') // @translate
                    . ' ' . $translator->translate('The site path will be automatically prepended.'), // @translate
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'o:index_id',
            'type' => Element\Select::class,
            'options' => [
                'label' => 'Index', // @translate
                'value_options' => $this->getIndexesOptions(),
                'empty_option' => 'Select an index below…', // @translate
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'o:form',
            'type' => Element\Select::class,
            'options' => [
                'label' => 'Form', // @translate
                'value_options' => $this->getFormsOptions(),
                'empty_option' => 'Select a form below…', // @translate
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'manage_page_default',
            'type' => SiteSelect::class,
            'options' => [
                'label' => 'Set as default search page for sites', // @translate
                'empty_option' => '[No change]', // @translate
                'info' => 'The page will be made available on all selected sites. This param can be set in each site settings too.', // @translate
                'prepend_value_options' => [
                    'all' => '[All sites]', // @translate
                    'admin' => 'Admin', // @translate
                ],
            ],
            'attributes' => [
                'id' => 'manage_page_default',
                'class' => 'chosen-select',
                'multiple' => true,
                'data-placeholder' => 'Select sites…', // @translate
            ],
        ]);

        $this->add([
            'name' => 'manage_page_availability',
            'type' => Element\Radio::class,
            'options' => [
                'label' => 'Availability on sites', // @translate
                'info' => 'The admin settings are not modified.', // @translate
                'value_options' => [
                    'disable' => 'Make unavailable in all sites', // @translate
                    'let' => 'Don’t modify', // @translate
                    'enable' => 'Make available in all sites', // @translate
                ],
            ],
            'attributes' => [
                'value' => 'let',
            ],
        ]);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'manage_page_availability',
            'required' => false,
        ]);
    }

    /**
     * @param ApiManager $apiManager
     */
    public function setApiManager(ApiManager $apiManager)
    {
        $this->apiManager = $apiManager;
    }

    public function getApiManager()
    {
        return $this->apiManager;
    }

    public function setFormAdapterManager($formAdapterManager)
    {
        $this->formAdapterManager = $formAdapterManager;
    }

    public function getFormAdapterManager()
    {
        return $this->formAdapterManager;
    }

    protected function getIndexesOptions()
    {
        $options = [];

        $api = $this->getApiManager();

        $indexes = $api->search('search_indexes')->getContent();
        foreach ($indexes as $index) {
            $options[$index->id()] =
                sprintf('%s (%s)', $index->name(), $index->adapterLabel());
        }

        return $options;
    }

    protected function getFormsOptions()
    {
        $options = [];

        $formAdapterManager = $this->getFormAdapterManager();
        $formAdapterNames = $formAdapterManager->getRegisteredNames();

        foreach ($formAdapterNames as $name) {
            $formAdapter = $formAdapterManager->get($name);
            $options[$name] = $formAdapter->getLabel();
        }

        return $options;
    }
}
