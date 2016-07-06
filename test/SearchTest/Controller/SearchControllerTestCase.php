<?php

namespace SearchTest\Controller;

use OmekaTestHelper\Controller\OmekaControllerTestCase;

abstract class SearchControllerTestCase extends OmekaControllerTestCase
{
    protected $searchIndex;
    protected $searchPage;

    public function setUp()
    {
        parent::setUp();

        $this->loginAsAdmin();

        $this->setupTestSearchAdapter();

        $response = $this->api()->create('search_indexes', [
            'o:name' => 'TestIndex',
            'o:adapter' => 'test',
            'o:settings' => [
                'resources' => [
                    'items',
                    'item_sets',
                ],
            ],
        ]);
        $searchIndex = $response->getContent();
        $response = $this->api()->create('search_pages', [
            'o:name' => 'TestPage',
            'o:path' => 'test/search',
            'o:index_id' => $searchIndex->id(),
            'o:form' => 'basic',
            'o:settings' => [
                'facets' => [],
                'sort_fields' => [],
            ],
        ]);
        $searchPage = $response->getContent();

        $this->searchIndex = $searchIndex;
        $this->searchPage = $searchPage;
    }

    public function tearDown()
    {
        $this->api()->delete('search_pages', $this->searchPage->id());
        $this->api()->delete('search_indexes', $this->searchIndex->id());
    }

    protected function setupTestSearchAdapter()
    {
        $serviceLocator = $this->getApplication()->getServiceManager();
        $config = $serviceLocator->get('Config');
        $config['search']['adapters']['test'] = 'Search\Test\Adapter\TestAdapter';
        $serviceLocator->setAllowOverride(true);
        $serviceLocator->setService('Config', $config);
        $serviceLocator->setAllowOverride(false);
    }

    protected function resetApplication()
    {
        parent::resetApplication();

        $this->setupTestSearchAdapter();
    }
}
