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

namespace Search\Form\Admin;

use Zend\Form\Element;
use Zend\Form\Fieldset;
use Zend\Form\Form;

class SearchPageConfigureForm extends Form
{
    protected $formElementManager;

    public function init()
    {
        /** @var \Search\Api\Representation\SearchPageRepresentation $searchPage */
        $searchPage = $this->getOption('search_page');
        $index = $searchPage->index();
        if (empty($index)) {
            return;
        }
        $this->addFacets();
        $this->addSortFields();
        $this->addFormFieldset();

        // Allow to manage the simple and visual form differently.
        $this->add([
            'name' => 'form_class',
            'type' => Element\Hidden::class,
            'attributes' => [
                'value' =>  get_class($this),
            ],
        ]);
    }

    protected function addFacets()
    {
        $this->addFacetLimit();

        /** @var \Search\Api\Representation\SearchPageRepresentation $searchPage */
        $searchPage = $this->getOption('search_page');
        $adapter = $searchPage->index()->adapter();

        $facets = new Fieldset('facets');
        $facets->setLabel('Facets'); // @translate
        $facets->setAttribute('data-sortable', '1');
        // TODO This attribute of the fieldset is not output for an unknown reason.
        $facets->setAttribute('data-ordered', '1');

        $facetFields = $adapter->getAvailableFacetFields($searchPage->index());
        $facetFields = $this->sortFields($facetFields, 'facets');
        $weights = range(0, count($facetFields));
        $weightOptions = array_combine($weights, $weights);
        $weight = 0;
        foreach ($facetFields as $field) {
            $fieldset = new Fieldset($field['name']);
            $fieldset->setLabel($this->getFacetFieldLabel($field));

            $displayFieldset = new Fieldset('display');
            $displayFieldset->add([
                'name' => 'label',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Label', // @translate
                ],
                'attributes' => [
                    'value' => isset($field['label']) ? $field['label'] : '',
                ],
            ]);
            $fieldset->add($displayFieldset);

            $fieldset->add([
                'name' => 'enabled',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Enabled', // @translate
                ],
            ]);

            $fieldset->add([
                'name' => 'weight',
                'type' => Element\Select::class,
                'options' => [
                    'label' => 'Weight', // @translate
                    'value_options' => $weightOptions,
                ],
                'attributes' => [
                    'value' => ++$weight,
                ],
            ]);

            $facets->add($fieldset);
        }

        $this->add($facets);
    }

    protected function addFacetLimit()
    {
        $this->add([
            'name' => 'facet_limit',
            'type' => Element\Number::class,
            'options' => [
                'label' => 'Facet limit', // @translate
                'info' => 'The maximum number of values fetched for each facet', // @translate
            ],
            'attributes' => [
                'value' => 10,
                'min' => 1,
                'required' => true,
            ],
        ]);
    }

    protected function addSortFields()
    {
        /** @var \Search\Api\Representation\SearchPageRepresentation $searchPage */
        $searchPage = $this->getOption('search_page');
        $adapter = $searchPage->index()->adapter();

        $sortFieldsFieldset = new Fieldset('sort_fields');
        $sortFieldsFieldset->setLabel('Sort fields'); // @translate
        $sortFieldsFieldset->setAttribute('data-sortable', '1');
        $sortFieldsFieldset->setAttribute('data-ordered', '1');

        $sortFields = $adapter->getAvailableSortFields($searchPage->index());
        $sortFields = $this->sortFields($sortFields, 'sort_fields');
        $weights = range(0, count($sortFields));
        $weightOptions = array_combine($weights, $weights);
        $weight = 0;
        foreach ($sortFields as $field) {
            $fieldset = new Fieldset($field['name']);
            $fieldset->setLabel($this->getSortFieldLabel($field));

            $displayFieldset = new Fieldset('display');
            $displayFieldset->add([
                'name' => 'label',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Label', // @translate
                ],
                'attributes' => [
                    'value' => isset($field['label']) ? $field['label'] : '',
                ],
            ]);
            $fieldset->add($displayFieldset);

            $fieldset->add([
                'name' => 'enabled',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Enabled', // @translate
                ],
            ]);

            $fieldset->add([
                'name' => 'weight',
                'type' => Element\Select::class,
                'options' => [
                    'label' => 'Weight', // @translate
                    'value_options' => $weightOptions,
                ],
                'attributes' => [
                    'value' => ++$weight,
                ],
            ]);

            $sortFieldsFieldset->add($fieldset);
        }

        $this->add($sortFieldsFieldset);
    }

    protected function addFormFieldset()
    {
        $formElementManager = $this->getFormElementManager();
        $searchPage = $this->getOption('search_page');

        $formAdapter = $searchPage->formAdapter();
        if (!isset($formAdapter)) {
            return;
        }

        $configFormClass = $formAdapter->getConfigFormClass();
        if (!isset($configFormClass)) {
            return;
        }

        $fieldset = $formElementManager->get($formAdapter->getConfigFormClass(), [
            'search_page' => $searchPage,
        ]);
        $fieldset->setName('form');
        $fieldset->setLabel('Form settings'); // @translate

        $this->add($fieldset);
    }

    /**
     * @param array $fields
     * @param string $type
     * @return array
     */
    protected function sortFields(array $fields, $type)
    {
        $searchPage = $this->getOption('search_page');
        $settings = $searchPage->settings();
        if (empty($settings) || empty($settings[$type])) {
            return $fields;
        }
        // Remove the keys that exists in settings, but not in fields to sort.
        $order = array_intersect_key($settings[$type], $fields);
        // Order the fields.
        $result = array_replace($order, $fields);
        return $result;
    }

    /**
     * @param string $field
     * @param string $settingsKey
     * @return string
     */
    protected function getFieldLabel($field, $settingsKey)
    {
        $searchPage = $this->getOption('search_page');
        $settings = $searchPage->settings();

        $name = $field['name'];
        $label = isset($field['label']) ? $field['label'] : null;
        if (isset($settings[$settingsKey][$name])) {
            $fieldSettings = $settings[$settingsKey][$name];
            if (isset($fieldSettings['display']['label'])) {
                $label = $fieldSettings['display']['label'];
            }
        }
        $label = $label ? sprintf('%s (%s)', $label, $field['name']) : $field['name'];
        return $label;
    }

    /**
     * @param string $field
     * @return string
     */
    protected function getFacetFieldLabel($field)
    {
        return $this->getFieldLabel($field, 'facets');
    }

    /**
     * @param string $field
     * @return string
     */
    protected function getSortFieldLabel($field)
    {
        return $this->getFieldLabel($field, 'sort_fields');
    }

    public function setFormElementManager($formElementManager)
    {
        $this->formElementManager = $formElementManager;
    }

    public function getFormElementManager()
    {
        return $this->formElementManager;
    }
}
