<?php
namespace Search\FormAdapter;

use Search\Query;

/**
 * Simulate an api search with the module search.
 *
 * Only main search and properties are managed currently, with the joiner "and".
 */
class ApiFormAdapter implements FormAdapterInterface
{
    public function getLabel()
    {
        return 'Api'; // @translate
    }

    public function getFormClass()
    {
        return null;
    }

    public function getFormPartial()
    {
        return null;
    }

    public function getConfigFormClass()
    {
        return \Search\Form\Admin\ApiFormConfigFieldset::class;
    }

    public function toQuery(array $request, array $formSettings)
    {
        $query = new Query();
        if (isset($request['search'])) {
            $query->setQuery($request['search']);
        }

        if (!empty($request['owner_id'])) {
            $this->addIntegersFilterToQuery($query, 'owner_id', $request['owner_id']);
        }

        if (!empty($request['resource_class_label'])) {
            $this->addTextsFilterToQuery($query, 'resource_class_label', $request['resource_class_label']);
        }

        if (!empty($request['resource_class_id'])) {
            $this->addIntegersFilterToQuery($query, 'resource_class_id', $request['resource_class_id']);
        }

        if (isset($request['resource_template_id']) && is_numeric($request['resource_template_id'])) {
            $this->addIntegersFilterToQuery($query, 'resource_template_id', $request['resource_template_id']);
        }

        // Copied from \Omeka\Api\Adapter\ItemAdapter::buildQuery()

        if (!empty($request['id'])) {
            $this->addIntegersFilterToQuery($query, 'id', $request['id']);
        }

        if (!empty($request['item_set_id'])) {
            $this->addIntegersFilterToQuery($query, 'item_set_id', $request['item_set_id']);
        }

        if (!empty($request['site_id']) && (int) $request['site_id']) {
            $query->setSiteId((int) $request['site_id']);
        }

        // Copied from \Omeka\Api\Adapter\ItemSetAdapter::buildQuery()

        if (isset($request['is_open'])) {
            $query->addFilter('is_open', (bool) $request['is_open']);
        }

        return $query;
    }

    /**
     * Apply search of properties into a search query.
     *
     * @see \Omeka\Api\Adapter\AbstractResourceEntityAdapter::buildPropertyQuery()
     *
     * @todo Manage negative search and missing parameters.
     *
     * @param Search$query
     * @param array $request
     */
    protected function buildPropertyQuery(Query $query, array $request)
    {
        if (!isset($request['property']) || !is_array($request['property'])) {
            return;
        }

        foreach ($request['property'] as $queryRow) {
            if (!(is_array($queryRow)
                && array_key_exists('property', $queryRow)
                && array_key_exists('type', $queryRow)
            )) {
                continue;
            }
            $property = $queryRow['property'];
            $queryType = $queryRow['type'];
            // $joiner = isset($queryRow['joiner']) ? $queryRow['joiner'] : null;
            $value = isset($queryRow['text']) ? $queryRow['text'] : null;

            if (!$value && $queryType !== 'nex' && $queryType !== 'ex') {
                continue;
            }

            // Narrow to specific property, if one is selected, else use search.
            $property = $this->normalizeProperty($property);
            // TODO Manage empty properties (main search).
            if (!$property) {
                continue;
            }

            // $positive = true;

            switch ($queryType) {
                case 'eq':
                    $query->addFilter($property, $value);
                    break;

                case 'nlist':
                case 'list':
                    $list = is_array($value) ? $value : explode("\n", $value);
                    $list = array_filter(array_map('trim', $list), 'strlen');
                    if (empty($list)) {
                        continue 2;
                    }
                    $value = $list;
                    // No break;
                case 'neq':
                case 'nin':
                case 'in':
                case 'nsw':
                case 'sw':
                case 'new':
                case 'ew':
                case 'nma':
                case 'ma':
                case 'nres':
                case 'res':
                case 'nex':
                case 'ex':
                    $query->addFilterQuery($property, $value, $queryType);
                    break;
                default:
                    continue 2;
            }
        }
    }

    /**
     * Get the term from a property string or integer.
     *
     * @todo Factorize with \Search\Mvc\Controller\Plugin\ApiSearch::normalizeProperty().
     *
     * @param string|int $property
     * @return string
     */
    protected function normalizeProperty($property)
    {
        if ($property) {
            if (is_numeric($property)) {
                try {
                    /** @var \Omeka\Api\Representation\PropertyRepresentation $property */
                    $property = $this->api->read('properties', ['id' => $property])->getContent();
                    return $property->term();
                } catch (\Omeka\Api\Exception\NotFoundException $e) {
                    return '';
                }
            }
            // TODO Check the property name of a request.
            return (string) $property;
        }
        return '';
    }

    /**
     * Add a filter for a single value.
     *
     * @param Query $query
     * @param string $filterName
     * @param string|array|int $value
     */
    protected function addTextFilterToQuery(Query $query, $filterName, $value)
    {
        $dataValues = trim(is_array($value) ? array_shift($value) : $value);
        if (strlen($dataValues)) {
            $query->addFilter($filterName, $dataValues);
        }
    }

    /**
     * Add a numeric filter for a single value.
     *
     * @param Query $query
     * @param string $filterName
     * @param string|array|int $value
     */
    protected function addIntegerFilterToQuery(Query $query, $filterName, $value)
    {
        $dataValues = (int) (is_array($value) ? array_shift($value) : $value);
        if ($dataValues) {
            $query->addFilter($filterName, $dataValues);
        }
    }

    /**
     * Add a filter for a value, and make it multiple.
     *
     * @param Query $query
     * @param string $filterName
     * @param string|array|int $value
     */
    protected function addTextsFilterToQuery(Query $query, $filterName, $value)
    {
        $dataValues = is_array($value) ? $value : [$value];
        $dataValues = array_filter(array_map('trim', $dataValues), 'strlen');
        if ($dataValues) {
            $query->addFilter($filterName, $dataValues);
        }
    }

    /**
     * Add a numeric filter for a value, and make it multiple.
     *
     * @param Query $query
     * @param string $filterName
     * @param string|array|int $value
     */
    protected function addIntegersFilterToQuery(Query $query, $filterName, $value)
    {
        $dataValues = is_array($value) ? $value : [$value];
        $dataValues = array_filter(array_map('intval', $dataValues));
        if ($dataValues) {
            $query->addFilter($filterName, $dataValues);
        }
    }
}
