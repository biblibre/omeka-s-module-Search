<?php

namespace SearchTest\Controller;

use Omeka\Test\AbstractHttpControllerTestCase;

abstract class SearchControllerTestCase extends AbstractHttpControllerTestCase
{
    protected $searchIndex;
    protected $searchPage;

    public function setUp(): void
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
            'o:form' => 'standard',
            'o:settings' => [
                'facets' => [],
                'sort_fields' => [],
            ],
        ]);
        $searchPage = $response->getContent();

        $this->searchIndex = $searchIndex;
        $this->searchPage = $searchPage;
    }

    public function tearDown(): void
    {
        $this->api()->delete('search_pages', $this->searchPage->id());
        $this->api()->delete('search_indexes', $this->searchIndex->id());
    }

    protected function setupTestSearchAdapter()
    {
        $serviceLocator = $this->getApplication()->getServiceManager();
        $adapterManager = $serviceLocator->get('Search\AdapterManager');
        $config = [
            'invokables' => [
                'test' => 'Search\Test\Adapter\TestAdapter',
            ],
        ];
        $adapterManager->configure($config);
    }

    protected function resetApplication()
    {
        $this->application = null;

        $this->setupTestSearchAdapter();
    }

    protected function getServiceLocator()
    {
        return $this->getApplication()->getServiceManager();
    }

    protected function api()
    {
        return $this->getServiceLocator()->get('Omeka\ApiManager');
    }

    protected function settings()
    {
        return $this->getServiceLocator()->get('Omeka\Settings');
    }

    protected function getEntityManager()
    {
        return $this->getServiceLocator()->get('Omeka\EntityManager');
    }

    protected function login($email, $password)
    {
        $serviceLocator = $this->getServiceLocator();
        $auth = $serviceLocator->get('Omeka\AuthenticationService');
        $adapter = $auth->getAdapter();
        $adapter->setIdentity($email);
        $adapter->setCredential($password);
        return $auth->authenticate();
    }

    protected function loginAsAdmin()
    {
        $this->login('admin@example.com', 'root');
    }

    protected function logout()
    {
        $serviceLocator = $this->getServiceLocator();
        $auth = $serviceLocator->get('Omeka\AuthenticationService');
        $auth->clearIdentity();
    }
}
