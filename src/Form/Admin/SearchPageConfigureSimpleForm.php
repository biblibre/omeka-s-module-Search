<?php

/*
 * Copyright Daniel Berthereau, 2019
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

class SearchPageConfigureSimpleForm extends SearchPageConfigureForm
{
    protected function addFacets()
    {
        $this->addFacetLimit();

        // field (term) | label (order means weight).
        $this->add([
            'name' => 'facets',
            'type' => Element\Textarea::class,
            'options' => [
                'label' => 'Facets', // @translate
                'info' => 'List of facets that will be displayed in the search page. Format is "term | Label".', // @translate
            ],
            'attributes' => [
                'id' => 'facets',
                'placeholder' => 'dcterms:subject | Subject (asc)',
            ],
        ]);

        $this->add([
            'name' => 'available_facets',
            'type' => Element\Textarea::class,
            'options' => [
                'label' => 'Available facets', // @translate
                'info' => 'List of all available facets, among which some can be copied above.', // @translate
            ],
            'attributes' => [
                'id' => 'available_facets',
                'placeholder' => 'dcterms:subject | Subject (asc)',
            ],
        ]);
    }

    protected function addSortFields()
    {
        // field (term + asc/desc) | label (+ asc/desc) (order means weight).
        $this->add([
            'name' => 'sort_fields',
            'type' => Element\Textarea::class,
            'options' => [
                'label' => 'Sort fields', // @translate
                'info' => 'List of sort fields that will be displayed in the search page. Format is "term dir | Label".', // @translate
            ],
            'attributes' => [
                'id' => 'sort_fields',
                'placeholder' => 'dcterms:subject asc | Subject (asc)',
            ],
        ]);

        $this->add([
            'name' => 'available_sort_fields',
            'type' => Element\Textarea::class,
            'options' => [
                'label' => 'Available sort fields', // @translate
                'info' => 'List of all available sort fields, among which some can be copied above.', // @translate
            ],
            'attributes' => [
                'id' => 'available_sort_fields',
                'placeholder' => 'dcterms:subject asc | Subject (asc)',
            ],
        ]);
    }

    // TODO setData() or populateValues() in order to manage formatting in form.
}
