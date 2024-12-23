<?php

namespace Search\Test\Controller\Admin;

use Search\Test\Controller\SearchControllerTestCase;

class SearchPageControllerTest extends SearchControllerTestCase
{
    public function testAddGetAction()
    {
        $this->dispatch('/admin/search/page/add');
        $this->assertResponseStatusCode(200);

        $this->assertQuery('input[name="o:name"]');
        $this->assertQuery('input[name="o:path"]');
        $this->assertQuery('select[name="o:index_id"]');
        $this->assertQuery('select[name="o:form"]');
    }

    public function testAddPostAction()
    {
        $forms = $this->getServiceLocator()->get('FormElementManager');
        $form = $forms->get('Search\Form\Admin\SearchPageAddForm');

        $this->dispatch('/admin/search/page/add', 'POST', [
            'o:name' => 'TestPage2',
            'o:path' => 'search/test2',
            'o:index_id' => $this->searchIndex->id(),
            'o:form' => 'standard',
            'csrf' => $form->get('csrf')->getValue(),
        ]);
        $response = $this->api()->search('search_pages', [
            'name' => 'TestPage2',
        ]);
        $searchPages = $response->getContent();
        $searchPage = reset($searchPages);
        $this->assertRedirectTo($searchPage->adminUrl('edit'));
    }

    public function testEditGetAction()
    {
        $this->dispatch($this->searchPage->adminUrl('edit'));
        $this->assertResponseStatusCode(200);

        $this->assertQueryContentContains('.field .field-meta label', 'Facets');
        $this->assertQueryContentContains('.field .field-meta label', 'Sort fields');
    }

    public function testEditPostAction()
    {
        $forms = $this->getServiceLocator()->get('FormElementManager');
        $form = $forms->get('Search\Form\Admin\SearchPageEditForm', [
            'search_page' => $this->searchPage,
        ]);

        $url = '/admin/search/page/' . $this->searchPage->id() . '/edit';
        $this->dispatch($url, 'POST', [
            'o:name' => 'TestPage2',
            'o:path' => 'search/test2',
            'o:settings' => [
                'facet_limit' => '10',
                'save_queries' => '1',
                'show_search_summary' => '1',
                'form' => [
                    'proximity' => '1',
                ],
            ],
            'csrf' => $form->get('csrf')->getValue(),
        ]);
        $this->assertRedirectTo("/admin/search");
    }
}
