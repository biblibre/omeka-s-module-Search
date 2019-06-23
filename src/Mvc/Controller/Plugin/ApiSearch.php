<?php
namespace Search\Mvc\Controller\Plugin;

use Doctrine\ORM\EntityManager;
use Omeka\Api\Adapter\Manager as AdapterManager;
use Omeka\Api\Exception;
use Omeka\Api\Manager;
use Omeka\Api\Request;
use Omeka\Api\Response;
use Omeka\Api\ResourceInterface;
use Omeka\Stdlib\Paginator;
use Omeka\Permissions\Acl;
use Search\Api\Representation\SearchIndexRepresentation;
use Search\Api\Representation\SearchPageRepresentation;
use Search\FormAdapter\ApiFormAdapter;
use Search\Querier\Exception\QuerierException;
use Search\Query;
use Search\Response as SearchResponse;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\Log\LoggerInterface;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\ServiceManager\Exception\ServiceNotFoundException;

/**
 * Do an api search via the default search index.
 *
 * Does the same than the Omeka api controller plugin for method search().
 * Allows to get a standard Omeka Response from the external engine.
 * @see \Omeka\Mvc\Controller\Plugin\Api
 *
 * Notes:
 * - Currently, many parameters are unavailable. Some methods miss in Query.
 * - The event "api.search.query" is not triggered.
 * - returnScalar is not managed.
 * - Ideally, the external search engine should answer like the api?
 *
 * @todo Convert in a standard api restful controller or in a standard page with the api form adapter.
 */
class ApiSearch extends AbstractPlugin
{
    /**
     * @var Manager
     */
    protected $api;

    /**
     * @var SearchPageRepresentation
     */
    protected $page;

    /**
     * @var SearchIndexRepresentation
     */
    protected $index;

    /**
     * @var AdapterManager
     */
    protected $adapterManager;

    /**
     * @var ApiFormAdapter
     */
    protected $apiFormAdapter;

    /**
     * @var Acl
     */
    protected $acl;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var Paginator
     */
    protected $paginator;

    /**
     * @param Manager $api
     * @param SearchPageRepresentation $page
     * @param SearchIndexRepresentation $index
     * @param AdapterManager $adapterManager
     * @param ApiFormAdapter $apiFormAdapter
     * @param Acl $acl
     * @param LoggerInterface $logger
     * @param TranslatorInterface $translator
     * @param EntityManager $entityManager
     * @param Paginator $paginator
     */
    public function __construct(
        Manager $api,
        SearchPageRepresentation $page = null,
        SearchIndexRepresentation $index = null,
        AdapterManager $adapterManager = null,
        ApiFormAdapter $apiFormAdapter = null,
        Acl $acl = null,
        LoggerInterface $logger = null,
        TranslatorInterface $translator = null,
        EntityManager $entityManager = null,
        Paginator $paginator = null
    ) {
        $this->api = $api;
        $this->page = $page;
        $this->index = $index;
        $this->adapterManager = $adapterManager;
        $this->apiFormAdapter = $apiFormAdapter;
        $this->acl = $acl;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->entityManager = $entityManager;
        $this->paginator = $paginator;
    }

    /**
     * Execute a search API request via a querier if available, else the api.
     *
     * The arguments are the same than \Omeka\Mvc\Controller\Plugin\Api::search().
     * Some features of the Omeka api may not be available.
     *
     * @see \Omeka\Api\Manager::search()
     *
     * @param string $resource
     * @param array $data
     * @return Response
     */
    public function __invoke($resource, array $data = [], array $options = [])
    {
        if (!$this->index) {
            // Unset the "index" option to avoid a loop.
            unset($data['index']);
            unset($options['index']);
            return $this->api->search($resource, $data, $options);
        }

        // Check it the resource is managed by this index.
        $searchIndexSettings = $this->index->settings();
        if (!in_array($resource, $searchIndexSettings['resources'])) {
            // Unset the "index" option to avoid a loop.
            unset($data['index']);
            unset($options['index']);
            return $this->api->search($resource, $data, $options);
        }

        $request = new Request(Request::SEARCH, $resource);
        $request->setContent($data)
            ->setOption($options);
        return $this->execute($request);
    }

    /**
     * Execute a request.
     *
     * @see \Omeka\Api\Manager::execute()
     *
     * @param Request $request
     * @return Response
     */
    protected function execute(Request $request)
    {
        // Copy of ApiManager, with adaptations and simplifications.
        $t = $this->translator;

        // Get the adapter.
        try {
            $adapter = $this->adapterManager->get($request->getResource());
        } catch (ServiceNotFoundException $e) {
            throw new Exception\BadRequestException(sprintf(
                $t->translate('The API does not support the "%s" resource.'), // @translate
                $request->getResource()
            ));
        }

        // Verify that the current user has general access to this resource.
        if (!$this->acl->userIsAllowed($adapter, $request->getOperation())) {
            throw new Exception\PermissionDeniedException(sprintf(
                $t->translate('Permission denied for the current user to %s the %s resource.'), // @translate
                $request->getOperation(),
                $adapter->getResourceId()
            ));
        }

        if ($request->getOption('initialize', true)) {
            $this->api->initialize($adapter, $request);
        }

        // This is the true request.
        $response = $this->doAdapterSearch($request);

        // Validate the response and response content.
        if (!$response instanceof Response) {
            throw new Exception\BadResponseException('The API response must implement Omeka\Api\Response');
        }

        $response->setRequest($request);

        // Return scalar content as-is; do not validate or finalize.
        // if (Request::SEARCH === $request->getOperation() && $request->getOption('returnScalar')) {
        //     return $response;
        // }

        $validateContent = function ($value) {
            if (!$value instanceof ResourceInterface) {
                throw new Exception\BadResponseException('API response content must implement Omeka\Api\ResourceInterface.');
            }
        };
        $content = $response->getContent();
        is_array($content) ? array_walk($content, $validateContent) : $validateContent($content);

        if ($request->getOption('finalize', true)) {
            $this->api->finalize($adapter, $request, $response);
        }

        return $response;
    }

    /**
     * Do the search via the index querier.
     *
     * @see \Omeka\Api\Adapter\AbstractResourceEntityAdapter
     * @see \Omeka\Api\Adapter\AbstractEntityAdapter
     * @see \Search\Controller\IndexController::searchAction()
     *
     * @param Request $request
     * @return Response
     */
    protected function doAdapterSearch(Request $request)
    {
        // TODO Manage all standard params.
        // See \Omeka\Api\Adapter\AbstractEntityAdapter::search() to normalize params.
        // See \Search\Controller\IndexController::searchAction() for process.
        // Currently, only manage simple search and common params.
        // This corresponds to the search page form, but for the api.
        $query = $request->getContent();

        // Set default query parameters
        if (! isset($query['page'])) {
            $query['page'] = null;
        }
        if (! isset($query['per_page'])) {
            $query['per_page'] = null;
        }
        if (! isset($query['limit'])) {
            $query['limit'] = null;
        }
        if (! isset($query['offset'])) {
            $query['offset'] = null;
        }
        if (! isset($query['sort_by'])) {
            $query['sort_by'] = null;
        }
        if (isset($query['sort_order'])
            && in_array(strtolower($query['sort_order']), ['asc', 'desc'])
        ) {
            $query['sort_order'] = strtolower($query['sort_order']);
        } else {
            // Sort order is not forced because it may be the inverse for score.
            $query['sort_order'] = null;
        }

        // There is no form validation/filter.

        // Begin building the search query.
        $resource = $request->getResource();
        $searchPageSettings = $this->page->settings();
        $searchFormSettings = isset($searchPageSettings['form'])
            ? $searchPageSettings['form']
            : ['options' => [], 'metadata' => [], 'properties' => [], 'sort_fields' => []];
        $searchFormSettings['resource'] = $resource;
        $searchQuery = $this->apiFormAdapter->toQuery($query, $searchFormSettings);
        $searchQuery->setResources([$resource]);

        // Note: the event search.query is not triggered.

        // Nevertheless, the "is public" is automatically forced for visitors.
        if (!$this->acl->getAuthenticationService()->hasIdentity()) {
            $searchQuery->setIsPublic(true);
        }

        // No site by default for the api (added by controller only).

        // Finish building the search query.
        // The default sort is the one of the search engine, so it is not added,
        // except if it is specifically set.
        $this->sortQuery($searchQuery, $query, $searchFormSettings['metadata'], $searchFormSettings['sort_fields']);
        $this->limitQuery($searchQuery, $query, $searchFormSettings['options']);
        // $searchQuery->addOrderBy("$entityClass.id", $query['sort_order']);

        // No filter for specific limits.

        // No facets for the api.

        // Send the query to the search engine.
        $index = $this->index;
        $indexSettings = $index->settings();

        /** @var \Search\Querier\QuerierInterface $querier */
        $querier = $index->querier();
        try {
            $searchResponse = $querier->query($searchQuery);
        } catch (QuerierException $e) {
            throw new Exception\BadResponseException($e->getMessage(), $e->getCode(), $e);
        }

        // TODO Manage returnScalar.

        $totalResults = array_map(function ($resource) use ($searchResponse) {
            return $searchResponse->getResourceTotalResults($resource);
        }, $indexSettings['resources']);

        // Get entities from the search response.
        $ids = $this->extractIdsFromResponse($searchResponse, $resource);
        $mapClasses = [
            'items' => \Omeka\Entity\Item::class,
            'item_sets' => \Omeka\Entity\ItemSet::class,
        ];
        $entityClass = $mapClasses[$resource];
        $repository = $this->entityManager->getRepository($entityClass);
        $entities = $repository->findBy([
            'id' => $ids,
        ]);

        // The original order of the ids must be kept.
        $orderedEntities = array_fill_keys($ids, null);
        foreach ($entities as $entity) {
            $orderedEntities[$entity->getId()] = $entity;
        }
        $entities = array_values(array_filter($orderedEntities));

        $response = new Response($entities);
        $response->setTotalResults($totalResults);
        return $response;
    }

    /**
     * Set sort_by and sort_order conditions to the query builder.
     *
     * @see \Omeka\Api\Adapter\AbstractResourceEntityAdapter::sortQuery()
     * @see \Omeka\Api\Adapter\AbstractEntityAdapter::sortQuery()
     *
     * @param Query $searchQuery
     * @param array $query
     * @param array $metadata
     * @param array $sortFields
     */
    protected function sortQuery(Query $searchQuery, array $query, array $metadata, array $sortFields)
    {
        if (empty($metadata) || empty($sortFields)) {
            return;
        }
        if (!is_string($query['sort_by'])) {
            return;
        }
        if (empty($metadata[$query['sort_by']])) {
            return;
        }
        $sortBy = $metadata[$query['sort_by']];

        if (isset($query['sort_order'])) {
            $sortOrder = strtolower($query['sort_order']);
            $sortOrder = $sortOrder === 'desc' ? 'desc' : 'asc';
        } else {
            $sortOrder = null;
        }

        $property = $this->normalizeProperty($sortBy);
        if ($property) {
            $sort = $sortOrder ? $property . ' ' . $sortOrder : $property;
        } elseif (in_array($sortBy, ['resource_class_label', 'owner_name'])) {
            $sort = $sortOrder ? $sortBy . ' ' . $sortOrder : $sortBy;
        } elseif (in_array($sortBy, ['id', 'is_public', 'created', 'modified'])) {
            $sort = $sortOrder ? $sortBy . ' ' . $sortOrder : $sortBy;
        } else {
            // Indicate that the sort is checked and that it will be default.
            $searchQuery->setSort(null);
            return;
        }

        // Check if the sort order is managed.
        if (in_array($sort, $sortFields)) {
            $searchQuery->setSort($sort);
        }

        // TODO Sort randomly is not managed (can be done partially in the view).
        // TODO Sort by item count is not managed.
        // Else sort by relevance (score) or by id?
    }

    /**
     * Set page, limit (max results) and offset (first result) conditions to the
     * query builder.
     *
     * @see \Omeka\Api\Adapter\AbstractEntityAdapter::limitQuery()
     *
     * @param Query $searchQuery
     * @param array $query
     * @param array $options
     */
    protected function limitQuery(Query $searchQuery, array $query, array $options)
    {
        if (is_numeric($query['page'])) {
            $page = $query['page'] > 0 ? (int) $query['page'] : 1;
            if (is_numeric($query['per_page']) && $query['per_page'] > 0) {
                $perPage = (int) $query['per_page'];
                $this->paginator->setPerPage($perPage);
            } else {
                $perPage = $this->paginator->getPerPage();
            }
            $searchQuery->setLimitPage($page, $perPage);
            return;
        }

        // Set the max limit.
        $maxResults = empty($options['max_results']) ? 1 : (int) $options['max_results'];

        // TODO Offset is not really managed in apiSearch (but rarely used).
        $limit = $query['limit'] > 0 ? min((int) $query['limit'], $maxResults) : $maxResults;
        $offset = $query['offset'] > 0 ? (int) $query['offset'] : null;
        if ($limit && $offset) {
            // TODO Check the formule to convert offset and limit to page and per page (rarely used).
            $page = $offset > $limit ? 1 + (int) (($offset - 1) / $limit) : 1;
            $searchQuery->setLimitPage($page, $limit);
        } elseif ($limit) {
            $searchQuery->setLimitPage(1, $limit);
        } elseif ($offset) {
            $searchQuery->setLimitPage($offset, 1);
        }
    }

    /**
     * Get the term from a property string or integer.
     *
     * @todo Factorize with \Search\FormAdapter\ApiFormAdapter::normalizeProperty().
     *
     * @param string|int $property
     * @return string
     */
    protected function normalizeProperty($property)
    {
        static $properties;

        if (!$property) {
            return '';
        }

        if (is_null($properties)) {
            $sql = <<<'SQL'
SELECT property.id, CONCAT(vocabulary.prefix, ":", property.local_name)
FROM property
JOIN vocabulary ON vocabulary.id = property.vocabulary_id
SQL;
            $properties = $this->entityManager->getConnection()
                ->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);
        }
        if (is_numeric($property)) {
            $property = (int) $property;
            return isset($properties[$property]) ? $properties[$property] : '';
        }
        $property = (string) $property;
        return in_array($property, $properties) ? $property : '';
    }

    /**
     * Extract ids from a search response.
     *
     * @param SearchResponse $searchResponse
     * @param string $resource
     * @return int[]
     */
    protected function extractIdsFromResponse(SearchResponse $searchResponse, $resource)
    {
        return array_map(function ($v) {
            return $v['id'];
        }, $searchResponse->getResults($resource));
    }
}
