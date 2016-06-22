<?php

namespace SearchTest\Controller\Admin;

require_once __DIR__ . '/../SearchControllerTestCase.php';

use Omeka\Mvc\Controller\Plugin\Messenger;
use SearchTest\Controller\SearchControllerTestCase;

class SearchIndexControllerTest extends SearchControllerTestCase
{
    public function testAddAction()
    {
        $this->dispatch('/admin/search/index/add');
        $this->assertResponseStatusCode(200);

        $this->assertQuery('input[name="o:name"]');
        $this->assertQuery('select[name="o:adapter"]');

        $forms = $this->getServiceLocator()->get('FormElementManager');
        $form = $forms->get('Search\Form\Admin\SearchIndexForm');

        $this->dispatch('/admin/search/index/add', 'POST', [
            'o:name' => 'TestIndex2',
            'o:adapter' => 'test',
            'csrf' => $form->get('csrf')->getValue(),
        ]);
        $response = $this->api()->search('search_indexes', [
            'name' => 'TestIndex2'
        ]);
        $searchIndexes = $response->getContent();
        $searchIndex = reset($searchIndexes);
        $this->assertRedirectTo($searchIndex->adminUrl('configure'));
    }

    public function testConfigureAction()
    {
        $this->dispatch($this->searchIndex->adminUrl('configure'));
        $this->assertResponseStatusCode(200);

        $this->assertQuery('input[name="resources[]"]');

        $forms = $this->getServiceLocator()->get('FormElementManager');
        $form = $forms->get('Search\Form\Admin\SearchIndexConfigureForm');

        $this->dispatch($this->searchIndex->adminUrl('configure'), 'POST', [
            'resources' => ['items', 'item_sets'],
            'csrf' => $form->get('csrf')->getValue(),
        ]);
        $this->assertRedirectTo("/admin/search");
    }

    public function testIndexAction()
    {
        $this->dispatch($this->searchIndex->adminUrl('index'));

        $this->assertRedirectTo("/admin/search");

        $messenger = new Messenger;
        $messages = $messenger->get();
        $message = $messages[Messenger::SUCCESS][0];
        $this->assertRegExp('/^Indexing in job ID \d+$/', $message);
    }
}
