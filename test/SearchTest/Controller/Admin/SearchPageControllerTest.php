<?php

namespace SearchTest\Controller\Admin;

require_once dirname(__DIR__) . '/SearchControllerTestCase.php';

use SearchTest\Controller\SearchControllerTestCase;

class SearchPageControllerTest extends SearchControllerTestCase
{
    public function testAddGetAction()
    {
        $this->dispatch('/admin/search-manager/page/add');
        $this->assertResponseStatusCode(200);

        $this->assertQuery('input[name="o:name"]');
        $this->assertQuery('input[name="o:path"]');
        $this->assertQuery('select[name="o:index_id"]');
        $this->assertQuery('select[name="o:form"]');
    }

    public function testAddPostAction()
    {
        $forms = $this->getServiceLocator()->get('FormElementManager');
        $form = $forms->get(\Search\Form\Admin\SearchPageForm::class);

        $this->dispatch('/admin/search-manager/page/add', 'POST', [
            'o:name' => 'TestPage [testAddPostAction]',
            'o:path' => 'search/test2',
            'o:index_id' => $this->searchIndex->id(),
            'o:form' => 'basic',
            'manage_page_default' => '0',
            'manage_page_availability' => 'let',
            'csrf' => $form->get('csrf')->getValue(),
        ]);
        $this->assertResponseStatusCode(302);
        $response = $this->api()->search('search_pages', [
            'name' => 'TestPage [testAddPostAction]',
        ]);
        $searchPages = $response->getContent();
        $this->assertNotEmpty($searchPages);
        $searchPage = reset($searchPages);
        $this->assertRedirectTo($searchPage->adminUrl('configure'));
    }

    public function testConfigureGetAction()
    {
        $this->dispatch($this->searchPage->adminUrl('configure'));
        $this->assertResponseStatusCode(200);

        $this->assertQueryContentContains('h2', 'Facets');
        $this->assertQueryContentContains('h2', 'Sort fields');
    }

    public function testConfigurePostAction()
    {
        $forms = $this->getServiceLocator()->get('FormElementManager');
        $form = $forms->get(\Search\Form\Admin\SearchPageConfigureForm::class, [
            'search_page' => $this->searchPage,
        ]);

        $url = '/admin/search-manager/page/' . $this->searchPage->id() . '/configure';
        $this->dispatch($url, 'POST', [
            'facet_limit' => '10',
            'csrf' => $form->get('csrf')->getValue(),
        ]);
        $this->assertRedirectTo("/admin/search-manager");
    }
}
