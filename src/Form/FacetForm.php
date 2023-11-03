<?php

namespace Search\Form;

use Laminas\Form\Element\Text;
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
    }
}
