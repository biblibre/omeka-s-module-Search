<?php

/*
 * Copyright BibLibre, 2020
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

namespace Search\Form;

use Laminas\Form\Fieldset;
use Laminas\I18n\Translator\TranslatorAwareInterface;
use Laminas\I18n\Translator\TranslatorAwareTrait;
use Laminas\InputFilter\InputFilterProviderInterface;

class StandardConfigForm extends Fieldset implements TranslatorAwareInterface, InputFilterProviderInterface
{
    use TranslatorAwareTrait;

    public function init()
    {
        $translator = $this->getTranslator();

        $search_fields_fieldset = new Fieldset('search_fields');
        $search_fields_fieldset->setLabel($translator->translate('Search fields'));
        $search_fields_fieldset->setAttribute('data-sortable', '1');

        $index = $this->getIndex();
        $searchFields = $index->adapter()->getAvailableSearchFields($index);
        $weights = range(0, count($searchFields));
        $weight_options = array_combine($weights, $weights);
        $weight = 0;
        foreach ($searchFields as $field) {
            $fieldset = new Fieldset($field['name']);
            $fieldset->setLabel($field['label']);

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

            $search_fields_fieldset->add($fieldset);
        }
        $this->add($search_fields_fieldset);

        $this->add([
            'name' => 'proximity',
            'type' => 'Checkbox',
            'options' => [
                'label' => $translator->translate('Proximity'),
                'info' => $translator->translate('Add proximity option on search form to choose distance between terms'),
            ],
        ]);

        $this->add([
            'name' => 'resource_class_field',
            'type' => 'Select',
            'options' => [
                'label' => $translator->translate('Resource class field'),
                'value_options' => $this->getAdapterFacetFieldsOptions(),
                'empty_option' => '',
            ],
        ]);

        $this->add([
            'name' => 'item_sets_field',
            'type' => 'Select',
            'options' => [
                'label' => $translator->translate('Item sets field'),
                'value_options' => $this->getAdapterFacetFieldsOptions(),
                'empty_option' => '',
            ],
        ]);
    }

    protected function getIndex()
    {
        $searchPage = $this->getOption('search_page');

        return $searchPage->index();
    }

    protected function getAdapterFacetFieldsOptions()
    {
        $index = $this->getIndex();
        $fields = $index->adapter()->getAvailableFacetFields($index);

        return array_column($fields, 'label', 'name');
    }

    public function getInputFilterSpecification()
    {
        return [
            'search_fields' => [
                'required' => false,
            ],
            'resource_class_field' => [
                'required' => false,
            ],
            'item_sets_field' => [
                'required' => false,
            ],
            'proximity' => [
                'required' => false,
            ],
        ];
    }
}
