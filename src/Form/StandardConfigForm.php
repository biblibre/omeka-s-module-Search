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

        $this->add([
            'name' => 'search_fields',
            'type' => 'MultiCheckbox',
            'options' => [
                'label' => $translator->translate('Search fields'),
                'value_options' => $this->getAdapterSearchFieldsOptions(),
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

    protected function getAdapterFacetFieldsOptions()
    {
        $searchPage = $this->getOption('search_page');
        $index = $searchPage->index();
        $fields = $index->adapter()->getAvailableFacetFields($index);

        return array_column($fields, 'label', 'name');
    }

    protected function getAdapterSearchFieldsOptions()
    {
        $searchPage = $this->getOption('search_page');
        $index = $searchPage->index();
        $fields = $index->adapter()->getAvailableSearchFields($index);

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
        ];
    }
}
