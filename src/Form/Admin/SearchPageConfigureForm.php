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

namespace Search\Form\Admin;

use Laminas\Form\Form;
use Laminas\Form\Fieldset;
use Laminas\I18n\Translator\TranslatorAwareInterface;
use Laminas\I18n\Translator\TranslatorAwareTrait;

class SearchPageConfigureForm extends Form implements TranslatorAwareInterface
{
    use TranslatorAwareTrait;

    protected $formElementManager;

    public function init()
    {
        $translator = $this->getTranslator();

        $searchPage = $this->getOption('search_page');
        $adapter = $searchPage->index()->adapter();
        $settings = $searchPage->settings();

        $this->add([
            'name' => 'spellchecking',
            'type' => 'Checkbox',
            'options' => [
                'label' => $translator->translate('Spellchecking'),
                'info' => $translator->translate('To correct query if a word or more are misspelled'),
            ],
        ]);

        $this->add([
            'name' => 'facet_limit',
            'type' => 'Number',
            'options' => [
                'label' => $translator->translate('Facet limit'),
                'info' => $translator->translate('The maximum number of values fetched for each facet'),
            ],
            'attributes' => [
                'min' => '1',
                'required' => true,
            ],
        ]);

        $facets = new Fieldset('facets');
        $facets->setLabel($translator->translate('Facets'));
        $facets->setAttribute('data-sortable', '1');

        $facetFields = $adapter->getAvailableFacetFields($searchPage->index());
        $weights = range(0, count($facetFields));
        $weight_options = array_combine($weights, $weights);
        $weight = 0;
        foreach ($facetFields as $field) {
            $fieldset = new Fieldset($field['name']);
            $fieldset->setLabel($this->getFacetFieldLabel($field));

            $displayFieldset = new Fieldset('display');
            $displayFieldset->add([
                'name' => 'label',
                'type' => 'Text',
                'options' => [
                    'label' => $translator->translate('Label'),
                ],
            ]);
            $fieldset->add($displayFieldset);

            $fieldset->add([
                'name' => 'enabled',
                'type' => 'Checkbox',
                'options' => [
                    'label' => $translator->translate('Enabled'),
                ],
            ]);

            $fieldset->add([
                'name' => 'weight',
                'type' => 'Select',
                'options' => [
                    'label' => $translator->translate('Weight'),
                    'value_options' => $weight_options,
                ],
                'attributes' => [
                    'value' => $weight++,
                ],
            ]);

            $facets->add($fieldset);
        }

        $this->add($facets);

        $sort_fields_fieldset = new Fieldset('sort_fields');
        $sort_fields_fieldset->setLabel($translator->translate('Sort fields'));
        $sort_fields_fieldset->setAttribute('data-sortable', '1');

        $sortFields = $adapter->getAvailableSortFields($searchPage->index());
        $weights = range(0, +count($sortFields));
        $weight_options = array_combine($weights, $weights);
        $weight = 0;
        foreach ($sortFields as $field) {
            $fieldset = new Fieldset($field['name']);
            $fieldset->setLabel($this->getSortFieldLabel($field));

            $displayFieldset = new Fieldset('display');
            $displayFieldset->add([
                'name' => 'label',
                'type' => 'Text',
                'options' => [
                    'label' => $translator->translate('Label'),
                ],
            ]);
            $fieldset->add($displayFieldset);

            $fieldset->add([
                'name' => 'enabled',
                'type' => 'Checkbox',
                'options' => [
                    'label' => $translator->translate('Enabled'),
                ],
            ]);

            $fieldset->add([
                'name' => 'weight',
                'type' => 'Select',
                'options' => [
                    'label' => $translator->translate('Weight'),
                    'value_options' => $weight_options,
                ],
                'attributes' => [
                    'value' => $weight++,
                ],
            ]);

            $sort_fields_fieldset->add($fieldset);
        }

        $this->add($sort_fields_fieldset);
        

        $formFieldset = $this->getFormFieldset();
        if ($formFieldset) {
            $this->add($formFieldset);
        }
    }

    public function setFormElementManager($formElementManager)
    {
        $this->formElementManager = $formElementManager;
    }

    public function getFormElementManager()
    {
        return $this->formElementManager;
    }

    protected function getFormFieldset()
    {
        $formElementManager = $this->getFormElementManager();
        $searchPage = $this->getOption('search_page');

        $formAdapter = $searchPage->formAdapter();
        if (!isset($formAdapter)) {
            return null;
        }

        $configFormClass = $formAdapter->getConfigFormClass();
        if (!isset($configFormClass)) {
            return null;
        }

        $fieldset = $formElementManager->get($configFormClass, [
            'search_page' => $searchPage,
        ]);
        $fieldset->setName('form');
        $fieldset->setLabel($this->getTranslator()->translate('Form settings'));


        return $fieldset;
    }

    protected function getFieldLabel($field, $settings_key)
    {
        $searchPage = $this->getOption('search_page');
        $settings = $searchPage->settings();

        $name = $field['name'];
        $label = isset($field['label']) ? $field['label'] : null;
        if (isset($settings[$settings_key][$name])) {
            $fieldSettings = $settings[$settings_key][$name];

            if (!empty($fieldSettings['display']['label'])) {
                $label = $fieldSettings['display']['label'];
            }
        }
        $label = $label ? sprintf('%s (%s)', $label, $field['name']) : $field['name'];

        return $label;
    }

    protected function getSortFieldLabel($field)
    {
        return $this->getFieldLabel($field, 'sort_fields');
    }

    protected function getFacetFieldLabel($field)
    {
        return $this->getFieldLabel($field, 'facets');
    }
}
