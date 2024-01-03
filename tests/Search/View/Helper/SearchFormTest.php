<?php

namespace Search\Test\View\Helper;

use Search\Test\Controller\SearchControllerTestCase;
use Search\Api\Representation\SearchIndexRepresentation;
use Search\Api\Representation\SearchPageRepresentation;

class SearchFormTest extends SearchControllerTestCase
{
    public function testGetAvailableSearchFields()
    {
        $searchIndexStub = $this->createStub(SearchIndexRepresentation::class);
        $searchIndexStub->method('availableSearchFields')->willReturn([
            ['name' => 'subject', 'label' => 'Subject', 'valid_operators' => ['equals']],
            ['name' => 'title', 'label' => 'Title', 'valid_operators' => ['starts_with']],
            ['name' => 'creator', 'label' => 'Creator', 'valid_operators' => ['ends_with']],
        ]);
        $searchPageStub = $this->createStub(SearchPageRepresentation::class);
        $searchPageStub->method('index')->willReturn($searchIndexStub);
        $searchPageStub->method('settings')->willReturn([
            'form' => [
                'search_fields' => [
                    ['name' => 'title'],
                    ['name' => 'creator', 'label' => ''],
                    ['name' => 'subject', 'label' => 'Topic'],
                ],
            ],
        ]);

        $viewHelpers = $this->getServiceLocator()->get('ViewHelperManager');
        $searchFormViewHelper = $viewHelpers->get('searchForm');
        $searchFields = $searchFormViewHelper($searchPageStub)->getAvailableSearchFields();

        $expected = [
            ['name' => 'title', 'label' => 'Title', 'valid_operators' => ['starts_with']],
            ['name' => 'creator', 'label' => 'Creator', 'valid_operators' => ['ends_with']],
            ['name' => 'subject', 'label' => 'Topic', 'valid_operators' => ['equals']],
        ];
        $this->assertEquals($expected, $searchFields);
    }
}
