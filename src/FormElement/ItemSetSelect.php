<?php

namespace Search\FormElement;

use Laminas\View\Renderer\PhpRenderer;
use Search\Api\Representation\SearchPageRepresentation;
use Search\Query;

class ItemSetSelect implements SearchFormElementInterface
{
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

    public function stringifyData(array $data, array $formElementData, $apiManager)
    {
        $dataString = '';

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
                $dataString = sprintf(" AND %s : ( %s )", $formElementData['field_name'], implode(', ', $titles));
            }
        }
        return $dataString;
    }
}
