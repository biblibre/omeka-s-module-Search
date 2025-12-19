<?php

namespace Search\Form;

use Laminas\Form\Element\Text;
use Laminas\Form\Element\Select;
use Laminas\Form\Element\Number;
use Laminas\Form\Form;
use Search\Form\Element\FacetValueRendererSelect;

class FacetForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'name',
            'type' => Text::class,
            'options' => [
                'label' => 'Name', // @translate
            ],
            'attributes' => [
                'disabled' => true,
            ],
        ]);

        $this->add([
            'name' => 'label',
            'type' => Text::class,
            'options' => [
                'label' => 'Label', // @translate
            ],
            'attributes' => [
                'data-field-data-key' => 'label',
            ],
        ]);

        if ($this->getOption('search_page')) {
            $searchPage = $this->getOption('search_page');
            $index = $searchPage->index();
            $facetSorts = $index->availableFacetSorts();

            $this->add([
                'name' => 'sort_by',
                'type' => Select::class,
                'options' => [
                    'label' => 'Sort by', // @translate
                    'empty_option' => 'Default', // @translate
                    'value_options' => $facetSorts,
                ],
                'attributes' => [
                    'data-field-data-key' => 'sort_by',
                ],
            ]);
        }

        $this->add([
            'name' => 'value_renderer',
            'type' => FacetValueRendererSelect::class,
            'options' => [
                'label' => 'Value renderer', // @translate
                'info' => 'The value renderer changes how a facet value is displayed to users. It is useful when the indexed value is an internal id or code that can be associated to a human-friendly text. The default renderer shows the indexed value as is.', // @translate
                'empty_option' => 'Default', // @translate
            ],
            'attributes' => [
                'data-field-data-key' => 'value_renderer',
            ],
        ]);

        $this->add([
            'name' => 'facet_limit',
            'type' => Number::class,
            'options' => [
                'label' => 'Facet fetched limit', // @translate
                'info' => 'The maximum number of values fetched', // @translate
            ],
            'attributes' => [
                'data-field-data-key' => 'facet_limit',
                'min' => '1',
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'facet_display_limit',
            'type' => Number::class,
            'options' => [
                'label' => 'Facet display limit', // @translate
                'info' => 'Limit number of values to display.', // @translate
            ],
            'attributes' => [
                'data-field-data-key' => 'facet_display_limit',
                'min' => '1',
                'required' => true,
            ],
        ]);
    }
}
