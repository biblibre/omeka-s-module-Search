<?php
namespace Search\Form;

use Omeka\View\Helper\Api;
use Zend\View\Helper\BasePath;
use Zend\Form\Element;
use Zend\Form\Fieldset;

class SettingsFieldset extends Fieldset
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * @var BasePath
     */
    protected $basePath;

    protected $label = 'Search (admin board)'; // @translate

    public function init()
    {
        $api = $this->getApi();
        $basePath = $this->getBasePath();
        $adminBasePath = $basePath('admin/');

        /** @var \Search\Api\Representation\SearchPageRepresentation[] $pages */
        $pages = $api->search('search_pages')->getContent();

        $valueOptions = [];
        $pageOptions = [];
        $apiOptions = [];
        foreach ($pages as $page) {
            $label = sprintf('%s (/%s)', $page->name(), $page->path());
            $valueOptions[$adminBasePath . $page->path()] = $label;
            $pageOptions[$page->id()] = $label;
            if ($page->formAdapter() instanceof \Search\FormAdapter\ApiFormAdapter) {
                $apiOptions[$page->id()] = $label;
            }
        }

        $this->add([
            'name' => 'search_main_page',
            'type' => Element\Select::class,
            'options' => [
                'label' => 'Default search page (admin)', // @translate
                'info' => 'This search engine is used in the admin bar.', // @translate
                'value_options' => $valueOptions,
                'empty_option' => 'Select the search engine for the admin bar…' // @translate
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
                'value_options' => $pageOptions,
            ],
            'attributes' => [
                'id' => 'search_pages',
            ],
        ]);

        $this->add([
            'name' => 'search_api_page',
            'type' => Element\Select::class,
            'options' => [
                'label' => 'Page used for quick api search', // @translate
                'info' => 'The method apiSearch() allows to do a quick search in some cases. It requires a mapping done with the Omeka api and the selected index.', // @translate
                'value_options' => $apiOptions,
                'empty_option' => 'Select the page for quick api search…', // @translate
            ],
            'attributes' => [
                'id' => 'search_api_page',
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

    /**
     * @param BasePath $basePath
     */
    public function setBasePath(BasePath $basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * @return \Zend\View\Helper\BasePath
     */
    public function getBasePath()
    {
        return $this->basePath;
    }
}
