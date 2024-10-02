<?php

namespace Search\FormElement;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Manager as ApiManager;
use Search\Api\Representation\SearchPageRepresentation;
use Search\Feature\SummarizeQueryInterface;
use Search\Query;

class ResourceClassSelect implements SearchFormElementInterface, SummarizeQueryInterface
{
    protected $api;
    protected $translator;

    public function getLabel(): string
    {
        return 'Resource class'; // @translate
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
        return $view->partial('common/advanced-search/resource-class', ['query' => $data]);
    }

    public function applyToQuery(Query $query, array $data, array $formElementData): void
    {
        if (!empty($data['resource_class_id'])) {
            $api = $this->getApiManager();
            $terms = [];
            foreach ($data['resource_class_id'] as $id) {
                try {
                    $resourceClass = $api->read('resource_classes', $id)->getContent();
                    $terms[] = $resourceClass->term();
                } catch (\Omeka\Api\Exception\NotFoundException $e) {
                }
            }

            if (!empty($terms)) {
                $query->addFacetFilter($formElementData['field_name'], $terms);
            }
        }
    }

    public function setApiManager(ApiManager $api): void
    {
        $this->api = $api;
    }

    public function getApiManager(): ApiManager
    {
        return $this->api;
    }

    public function setTranslator($translator): void
    {
        $this->translator = $translator;
    }

    public function getTranslator()
    {
        return $this->translator;
    }

    public function summarizeQuery($data, $page): array
    {
        $summarizeElement = [];
        $apiManager = $this->getApiManager();
        $translator = $this->getTranslator();

        if (!empty($data['resource_class_id'])) {
            $terms = [];
            foreach ($data['resource_class_id'] as $id) {
                try {
                    $resourceClass = $apiManager->read('resource_classes', $id)->getContent();
                    $terms[] = $resourceClass->term();
                } catch (\Omeka\Api\Exception\NotFoundException $e) {
                }
            }

            if (!empty($terms)) {
                $description = $translator->translate("Resource classes");
                $summarizeElement['name'] = $description;
                $summarizeElement['value'] = sprintf("%s : ( %s )", $description, implode(', ', $terms));
            }
        }

        return $summarizeElement;
    }
}
