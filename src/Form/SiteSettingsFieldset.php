<?php
namespace Search\Form;

use Omeka\View\Helper\Api;
use Zend\Form\Element;
use Zend\Form\Fieldset;

class SiteSettingsFieldset extends Fieldset
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * Warning: there is a core fieldset "Search".
     * @var string
     */
    protected $label = 'Search module'; // @translate

    public function init()
    {
        $api = $this->getApi();

        /** @var \Search\Api\Representation\SearchPageRepresentation[] $pages */
        $pages = $api->search('search_pages')->getContent();

        $valueOptions = [];
        foreach ($pages as $page) {
            $valueOptions[$page->id()] = sprintf('%s (/%s)', $page->name(), $page->path());
        }

        $this->add([
            'name' => 'search_main_page',
            'type' => Element\Select::class,
            'options' => [
                'label' => 'Default search page', // @translate
                'value_options' => $valueOptions,
                'empty_option' => 'Select the default search engine for the site…', // @translate
            ],
            'attributes' => [
                'id' => 'search_main_page',
            ],
        ]);

        $this->add([
            'name' => 'search_pages',
            'type' => Element\MultiCheckbox::class,
            'options' => [
                'label' => 'Available search pages', // @translate
                'value_options' => $valueOptions,
            ],
            'attributes' => [
                'id' => 'search_pages',
            ],
        ]);
    }

    /**
     * @param Api $api
     */
    public function setApi(Api $api)
    {
        $this->api = $api;
    }

    /**
     * @return \Omeka\View\Helper\Api
     */
    public function getApi()
    {
        return $this->api;
    }
}
