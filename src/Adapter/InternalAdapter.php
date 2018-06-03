<?php

namespace Search\Adapter;

use Omeka\Api\Manager as ApiManager;
use Search\Api\Representation\SearchIndexRepresentation;
use Zend\I18n\Translator\TranslatorInterface;

class InternalAdapter extends AbstractAdapter
{
    /**
     * @param ApiManager $api
     */
    protected $api;

    /**
     * @param TranslatorInterface $translator
     */
    protected $translator;

    /**
     * @param ApiManager $api
     * @param TranslatorInterface $translator
     */
    public function __construct(ApiManager $api, TranslatorInterface $translator)
    {
        $this->api = $api;
        $this->translator = $translator;
    }

    public function getLabel()
    {
        return 'Internal'; // @translate
    }

    public function getConfigFieldset()
    {
        return null;
    }

    public function getIndexerClass()
    {
        return \Search\Indexer\InternalIndexer::class;
    }

    public function getQuerierClass()
    {
        return \Search\Querier\InternalQuerier::class;
    }

    public function getAvailableFacetFields(SearchIndexRepresentation $index)
    {
        return $this->getAvailableFields($index);
    }

    public function getAvailableSortFields(SearchIndexRepresentation $index)
    {
        $availableFields = $this->getAvailableFields($index);

        // There is no default score sort.
        $sortFields = [];

        $directionLabel = [
            'asc' => $this->translator->translate('Asc'),
            'desc' => $this->translator->translate('Desc'),
        ];

        foreach ($availableFields as $name => $availableField) {
            $fieldName = $availableField['name'];
            $fieldLabel = $availableField['label'];
            foreach (['asc' => 'Asc', 'desc' => 'Desc'] as $direction => $labelDirection) {
                $name = $fieldName . ' ' . $direction;
                $sortFields[$name] = [
                    'name' => $name,
                    'label' => $fieldLabel ? $fieldLabel . ' ' . $labelDirection : '',
                ];
            }
        }

        return $sortFields;
    }

    public function getAvailableFields(SearchIndexRepresentation $index)
    {
        $response = $this->api->search('properties');

        // TODO Fix the page(create facet and sort tabs + property selector, like other views).
        // An overload may occur (memory_limit = 128M). Furthermore, there is a
        // limit for number of fields by request (max_input_vars = 1000).
        // And 3 fields by property, as facet and sort in 2 directions.
        $totalResults = $response->getTotalResults();
        if ($totalResults > 60) {
            $response = $this->api->search('properties', ['vocabulary_prefix' => 'dcterms']);
        }
        /** @var \Omeka\Api\Representation\PropertyRepresentation[] $properties */
        $properties = $response->getContent();

        $fields = [];
        foreach ($properties as $property) {
            $name = $property->term();
            // TODO Use an alternative label for the facets?
            $label = $property->label();
            $fields[$name] = [
                'name' => $name,
                'label' => $label,
            ];
        }

        return $fields;
    }
}
