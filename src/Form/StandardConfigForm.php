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
use Search\Form\Element\Fields;

class StandardConfigForm extends Fieldset
{
    protected $urlViewHelper;

    protected $searchFormElementManager;

    public function init()
    {
        $url = $this->urlViewHelper;

        $searchPage = $this->getOption('search_page');
        $index = $searchPage->index();
        $searchFields = $index->availableSearchFields();
        $searchFieldValueOptions = array_column($searchFields, 'label', 'name');
        $this->add([
            'name' => 'search_fields',
            'type' => Fields::class,
            'options' => [
                'label' => 'Search fields', // @translate
                'empty_option' => 'Add a search field', // @translate
                'value_options' => $searchFieldValueOptions,
                'field_list_url' => $url('admin/search/search-fields', ['action' => 'field-list'], ['query' => ['search_page_id' => $searchPage->id()]]),
                'field_row_url' => $url('admin/search/search-fields', ['action' => 'field-row'], ['query' => ['search_page_id' => $searchPage->id()]]),
                'field_edit_sidebar_url' => $url('admin/search/search-fields', ['action' => 'field-edit-sidebar'], ['query' => ['search_page_id' => $searchPage->id()]]),
            ],
        ]);

        $this->add([
            'name' => 'proximity',
            'type' => 'Checkbox',
            'options' => [
                'label' => 'Proximity', // @translate
                'info' => 'Add proximity option on search form to choose distance between terms', // @translate
            ],
        ]);

        $searchFormElementNames = $this->searchFormElementManager->getRegisteredNames($sortAlpha = true);
        $searchFormElementValueOptions = [];
        foreach ($searchFormElementNames as $name) {
            $searchFormElement = $this->searchFormElementManager->get($name);
            $searchFormElementValueOptions[] = [
                'value' => $name,
                'label' => $searchFormElement->getLabel(),
                'attributes' => [
                    'data-repeatable' => $searchFormElement->isRepeatable() ? '1' : '',
                ]
            ];
        }

        $this->add([
            'name' => 'elements',
            'type' => Fields::class,
            'options' => [
                'label' => 'Form elements', // @translate
                'empty_option' => 'Add a form element', // @translate
                'value_options' => $searchFormElementValueOptions,
                'field_list_url' => $url('admin/search/form-elements', ['action' => 'field-list'], ['query' => ['search_page_id' => $searchPage->id()]]),
                'field_row_url' => $url('admin/search/form-elements', ['action' => 'field-row'], ['query' => ['search_page_id' => $searchPage->id()]]),
                'field_edit_sidebar_url' => $url('admin/search/form-elements', ['action' => 'field-edit-sidebar'], ['query' => ['search_page_id' => $searchPage->id()]]),
            ],
        ]);
    }

    public function setUrlViewHelper($urlViewHelper)
    {
        $this->urlViewHelper = $urlViewHelper;
    }

    public function setSearchFormElementManager($searchFormElementManager): void
    {
        $this->searchFormElementManager = $searchFormElementManager;
    }
}
