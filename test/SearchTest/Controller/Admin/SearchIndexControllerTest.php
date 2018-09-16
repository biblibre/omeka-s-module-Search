<?php

namespace SearchTest\Controller\Admin;

require_once dirname(__DIR__) . '/SearchControllerTestCase.php';

use Omeka\Mvc\Controller\Plugin\Messenger;
use Omeka\Stdlib\Message;
use Search\Form\Admin\SearchIndexConfigureForm;
use SearchTest\Controller\SearchControllerTestCase;

class SearchIndexControllerTest extends SearchControllerTestCase
{
    public function testAddGetAction()
    {
        $this->dispatch('/admin/search-manager/index/add');
        $this->assertResponseStatusCode(200);

        $this->assertQuery('input[name="o:name"]');
        $this->assertQuery('select[name="o:adapter"]');
    }

    public function testAddPostAction()
    {
        $forms = $this->getServiceLocator()->get('FormElementManager');
        $form = $forms->get(\Search\Form\Admin\SearchIndexForm::class);

        $this->dispatch('/admin/search-manager/index/add', 'POST', [
            'o:name' => 'TestIndex2',
            'o:adapter' => 'test',
            'csrf' => $form->get('csrf')->getValue(),
        ]);
        $response = $this->api()->search('search_indexes', [
            'name' => 'TestIndex2',
        ]);
        $searchIndexes = $response->getContent();
        $searchIndex = reset($searchIndexes);
        $this->assertRedirectTo($searchIndex->adminUrl('edit'));
    }

    public function testConfigureGetAction()
    {
        $this->dispatch($this->searchIndex->adminUrl('edit'));
        $this->assertResponseStatusCode(200);

        $this->assertQuery('input[name="resources[]"]');
    }

    public function testConfigurePostAction()
    {
        $forms = $this->getServiceLocator()->get('FormElementManager');
        $form = $forms->get(SearchIndexConfigureForm::class, [
            'search_index_id' => $this->searchIndex->id(),
        ]);

        $this->dispatch($this->searchIndex->adminUrl('edit'), 'POST', [
            'resources' => ['items', 'item_sets'],
            'csrf' => $form->get('csrf')->getValue(),
        ]);
        $this->assertRedirectTo('/admin/search-manager');
    }

    public function testIndexAction()
    {
        $this->dispatch($this->searchIndex->adminUrl('index'));

        $this->assertRedirectTo('/admin/search-manager');

        $messenger = new Messenger;
        $messages = $messenger->get();
        $message = $messages[Messenger::SUCCESS][0];
        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals('Indexing of "%s" started in %sjob %s%s', $message->getMessage());
    }
}
