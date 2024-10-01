<?php

namespace Search\FormElement;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Manager as ApiManager;
use Search\Api\Representation\SearchPageRepresentation;
use Search\Query;

class ItemSetSelect implements SearchFormElementInterface
{
    protected $api;

    public function getLabel(): string
    {
        return 'Item sets'; // @translate
    }

    public function getConfigForm(SearchPageRepresentation $searchPage, PhpRenderer $view, array $formElementData): string
    {
        $availableFacetFields = $searchPage->index()->availableFacetFields();

        $fieldNameSelect = new \Laminas\Form\Element\Select('field_name');
        $fieldNameSelect->setLabel('Field'); // @translate
        $fieldNameSelect->setOption('info', 'Field to use for filtering. It should be a facet field.'); // @translate
        $fieldNameSelect->setValueOptions(array_column($availableFacetFields, 'label', 'name'));
        $fieldNameSelect->setValue($formElementData['field_name'] ?? '');
        $fieldNameSelect->setAttribute('data-field-data-key', 'field_name');
        $fieldNameSelect->setAttribute('required', true);

        return $view->formRow($fieldNameSelect);
    }

    public function isRepeatable(): bool
    {
        return false;
    }

    public function getForm(SearchPageRepresentation $searchPage, PhpRenderer $view, array $data, array $formElementData): string
    {
        return $view->partial('search/form-element/item-set-select', ['query' => $data]);
    }

    public function applyToQuery(Query $query, array $data, array $formElementData): void
    {
        if (!empty($data['item_set_id'])) {
            $query->addFacetFilter($formElementData['field_name'], array_filter($data['item_set_id']));
        }
    }

    public function setApiManager(ApiManager $api): void
    {
        $this->api = $api;
    }

    public function getApiManager(): ApiManager
    {
        // var_dump(($this->api));
        return $this->api;
    }

    public function stringifyData(array $data, array $formElementData) : string
    {
        $dataString = '';
        $apiManager = $this->getApiManager();

        if (!empty($data['item_set_id'])) {
            $titles = [];
            foreach ($data['item_set_id'] as $id) {
                try {
                    $itemSet = $apiManager->read('item_sets', $id)->getContent();
                    $titles[] = $itemSet->title();
                } catch (\Omeka\Api\Exception\NotFoundException $e) {
                }
            }

            if (!empty($titles)) {
                $description = "Item sets"; // @translate
                $dataString = sprintf("%s : ( %s )", $description, implode(', ', $titles));
            }
        }
        return $dataString;
    }
}
