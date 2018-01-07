<?php

namespace SearchTest\Controller;

require_once __DIR__ . '/SearchControllerTestCase.php';

class IndexControllerTest extends SearchControllerTestCase
{
    public function setUp()
    {
        parent::setUp();

        $response = $this->api()->create('sites', [
            'o:title' => 'Test site',
            'o:slug' => 'test',
            'o:theme' => 'default',
        ]);
        $this->site = $response->getContent();

        $siteSettings = $this->getServiceLocator()->get('Omeka\Settings\Site');
        $siteSettings->setTargetId($this->site->id());
        $siteSettings->set('search_pages', [$this->searchPage->id()]);

        $this->resetApplication();
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->api()->delete('sites', $this->site->id());
    }

    public function testSearchAction()
    {
        $this->dispatch('/s/test/test/search');
        $this->assertResponseStatusCode(200);

        $this->assertQuery('input[name="q"]');
        $this->assertNotQuery('.search-results');
    }

    public function testSearchWithParamsAction()
    {
        $this->dispatch('/s/test/test/search', 'GET', ['q' => 'test']);
        $this->assertResponseStatusCode(200);
        $this->assertQuery('.search-results');
        $this->assertQuery('input[name="q"][value="test"]');
    }
}
