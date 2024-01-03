<?php

namespace Search\Test\Controller\Admin;

use Omeka\Mvc\Controller\Plugin\Messenger;
use Omeka\Stdlib\Message;
use Search\Form\Admin\SearchIndexConfigureForm;
use Search\Form\Admin\SearchIndexRebuildForm;
use Search\Test\Controller\SearchControllerTestCase;

class SearchIndexControllerTest extends SearchControllerTestCase
{
    public function testAddGetAction()
    {
        $this->dispatch('/admin/search/index/add');
        $this->assertResponseStatusCode(200);

        $this->assertQuery('input[name="o:name"]');
        $this->assertQuery('select[name="o:adapter"]');
    }

    public function testAddPostAction()
    {
        $forms = $this->getServiceLocator()->get('FormElementManager');
        $form = $forms->get('Search\Form\Admin\SearchIndexForm');

        $this->dispatch('/admin/search/index/add', 'POST', [
            'o:name' => 'TestIndex2',
            'o:adapter' => 'test',
            'csrf' => $form->get('csrf')->getValue(),
        ]);
        $response = $this->api()->search('search_indexes', [
            'name' => 'TestIndex2',
        ]);
        $searchIndexes = $response->getContent();
        $searchIndex = reset($searchIndexes);
        $this->assertRedirectTo($searchIndex->adminUrl('configure'));
    }

    public function testConfigureGetAction()
    {
        $this->dispatch($this->searchIndex->adminUrl('configure'));
        $this->assertResponseStatusCode(200);

        $this->assertQuery('input[name="resources[]"]');
    }

    public function testConfigurePostAction()
    {
        $forms = $this->getServiceLocator()->get('FormElementManager');
        $form = $forms->get(SearchIndexConfigureForm::class, [
            'search_index_id' => $this->searchIndex->id(),
        ]);

        $this->dispatch($this->searchIndex->adminUrl('configure'), 'POST', [
            'resources' => ['items', 'item_sets'],
            'csrf' => $form->get('csrf')->getValue(),
        ]);
        $this->assertRedirectTo("/admin/search");
    }

    public function testIndexAction()
    {
        $forms = $this->getServiceLocator()->get('FormElementManager');
        $form = $forms->get(SearchIndexRebuildForm::class);

        $this->dispatch($this->searchIndex->adminUrl('rebuild'), 'POST', [
            'clear-index' => '0',
            'batch-size' => '100',
            'csrf' => $form->get('csrf')->getValue(),
        ]);

        $this->assertRedirectTo("/admin/search");

        $messenger = new Messenger;
        $messages = $messenger->get();
        $message = $messages[Messenger::SUCCESS][0];
        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals('Index rebuilding started in %sjob %s%s', $message->getMessage());
    }
}
