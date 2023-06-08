<?php

namespace Search\Form;

use Laminas\Form\Form;
use Laminas\Form\Element;

class SaveQueryForm extends Form
{
    protected $urlHelper;

    public function init()
    {
        $urlHelper = $this->getUrlHelper();
        $this->setAttribute('id', 'save-query-form');
        $this->setAttribute('action', $urlHelper('site/save-query', [], [], true));

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
                'placeholder' => 'Description', // @translate
            ],
        ]);
    }

    public function setUrlHelper($urlHelper)
    {
        $this->urlHelper = $urlHelper;
        return $this;
    }

    public function getUrlHelper()
    {
        return $this->urlHelper;
    }
}
