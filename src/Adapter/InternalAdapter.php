<?php

namespace Search\Adapter;

use Search\Api\Representation\SearchIndexRepresentation;

class InternalAdapter extends AbstractAdapter
{
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

    public function getAvailableFields(SearchIndexRepresentation $index)
    {
        // Use a direct query to avoid a memory overload when there are many
        // vocabularies.
        /* @var \Doctrine\DBAL\Connection $connection */
        $connection = $this->getServiceLocator()->get('Omeka\Connection');

        $qb = $connection->createQueryBuilder();
        $qb
            ->select(
                'CONCAT(vocabulary.prefix, ":", property.local_name) AS "name"',
                'property.label AS "label"'
            )
            ->from('property', 'property')
            ->innerJoin('property', 'vocabulary', 'vocabulary', 'property.vocabulary_id = vocabulary.id')
            ->addOrderBy('vocabulary.id', 'ASC')
            ->addOrderBy('property.local_name', 'ASC');

        $stmt = $connection->executeQuery($qb);
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $fields = [];
        foreach ($result as $field) {
            $fields[$field['name']] = $field;
        }

        return $fields;
    }

    public function getAvailableSortFields(SearchIndexRepresentation $index)
    {
        $availableFields = $this->getAvailableFields($index);

        // There is no default score sort.
        $sortFields = [];

        $translator = $this->getServiceLocator()->get('MvcTranslator');

        $directionLabels = [
            'asc' => $translator->translate('Asc'),
            'desc' => $translator->translate('Desc'),
        ];

        foreach ($availableFields as $name => $availableField) {
            $fieldName = $availableField['name'];
            $fieldLabel = $availableField['label'];
            foreach ($directionLabels as $direction => $labelDirection) {
                $name = $fieldName . ' ' . $direction;
                $sortFields[$name] = [
                    'name' => $name,
                    'label' => $fieldLabel ? $fieldLabel . ' ' . $labelDirection : '',
                ];
            }
        }

        return $sortFields;
    }

    public function getAvailableFacetFields(SearchIndexRepresentation $index)
    {
        return $this->getAvailableFields($index);
    }
}
