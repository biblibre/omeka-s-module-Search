<?php

namespace Search\Form;

use Laminas\Form\Form;
use Laminas\Form\Element;

class SaveQueryForm extends Form
{
    public function init()
    {
        $this->setAttribute('id', 'save-query-form');
        $this->setAttribute('action', 'save-query');

        $this->add([
            'name' => 'site_id',
            'type' => 'hidden',
            'attributes' => [
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'search_page_id',
            'type' => 'hidden',
            'attributes' => [
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'query_string',
            'type' => 'hidden',
            'attributes' => [
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'query_title',
            'type' => Element\Text::class,
            'attributes' => [
                'required' => true,
                'placeholder' => 'Title', // @translate
            ],
        ]);

        $this->add([
            'name' => 'query_description',
            'type' => Element\Textarea::class,
            'attributes' => [
                'required' => true,
                'placeholder' => 'Description', // @translate
            ],
        ]);
    }
}
