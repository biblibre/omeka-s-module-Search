<?php
namespace Search\Mvc\Controller\Plugin;

use Doctrine\ORM\EntityManager;
use Omeka\Api\Adapter\Manager as AdapterManager;
use Omeka\Api\Exception;
use Omeka\Api\Manager;
use Omeka\Api\Request;
use Omeka\Api\Response;
use Omeka\Api\ResourceInterface;
use Omeka\Permissions\Acl;
use Search\Api\Representation\SearchIndexRepresentation;
use Search\FormAdapter\ApiFormAdapter;
use Search\Querier\Exception\QuerierException;
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
     * @var int
     */
    protected $perPage;

    /**
     * @param Manager $api
     * @param SearchIndexRepresentation $index
     * @param AdapterManager $adapterManager
     * @param ApiFormAdapter $apiFormAdapter
     * @param Acl $acl
     * @param LoggerInterface $logger
     * @param TranslatorInterface $translator
     * @param EntityManager $entityManager
     * @param int $perPage
     */
    public function __construct(
        Manager $api,
        SearchIndexRepresentation $index = null,
        AdapterManager $adapterManager = null,
        ApiFormAdapter $apiFormAdapter = null,
        Acl $acl = null,
        LoggerInterface $logger = null,
        TranslatorInterface $translator = null,
        EntityManager $entityManager = null,
        $perPage = null
    ) {
        $this->api = $api;
        $this->index = $index;
        $this->adapterManager = $adapterManager;
        $this->apiFormAdapter = $apiFormAdapter;
        $this->acl = $acl;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->entityManager = $entityManager;
        $this->perPage = $perPage;
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
            return $this->api->search($resource, $data, $options);
        }

        // Check it the resource is managed by this index.
        $searchIndexSettings = $this->index->settings();
        if (!in_array($resource, $searchIndexSettings['resources'])) {
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
                $t->translate('The API does not support the "%s" resource.'),
                $request->getResource()
            ));
        }

        // Verify that the current user has general access to this resource.
        if (!$this->acl->userIsAllowed($adapter, $request->getOperation())) {
            throw new Exception\PermissionDeniedException(sprintf(
                $t->translate('Permission denied for the current user to %s the %s resource.'),
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

        $params = $request->getContent();

        // There is no form validation/filter.

        // Prepare the query.
        $resource = $request->getResource();
        $searchFormSettings = ['resource' => $resource];
        $query = $this->apiFormAdapter->toQuery($params, $searchFormSettings);

        // Add global parameters to the query.
        $index = $this->index;
        $indexSettings = $index->settings();

        if (!$this->acl->getAuthenticationService()->hasIdentity()) {
            $query->setIsPublic(true);
        }

        // No site by default for the api (added by controller only).

        $query->setResources([$resource]);

        if (!empty($params['sort_by'])) {
            if (isset($params['sort_order'])) {
                $sortOrder = strtolower($params['sort_order']);
                $sortOrder = $sortOrder === 'desc' ? 'desc' : 'asc';
            } else {
                $sortOrder = 'asc';
            }
            $sort = $params['sort_by'];
            $property = $this->normalizeProperty($sort);
            if ($property) {
                $query->setSort($property . ' ' . $sortOrder);
            } elseif (in_array($sort, ['resource_class_label', 'owner_name'])) {
                $query->setSort($sort . ' ' . $sortOrder);
            } elseif (in_array($sort, ['id', 'is_public', 'created', 'modified'])) {
                $query->setSort($sort . ' ' . $sortOrder);
            }
            // TODO Sort order is not managed.
            // TODO Sort randomly is not managed.
            // TODO Sort by item count is not managed.
        }
        // Else sort by relevance.

        // No filter for specific limits.

        // For pagination, params "limit" and "offset" are not managed.
        if (!empty($params['page']) && (int) $params['page']) {
            $pageNumber = (int) $params['page'];
            // $this->paginator->setCurrentPage($pageNumber);
            // TODO "per_page" is currently not managed (add a limit).
            // if (!empty($params['per_page']) && (int) $params['per_page']) {
            //     $perPage = (int) $params['per_page'];
            //     $this->paginator->setPerPage($perPage);
            // }
        } else {
            $pageNumber = 1;
        }
        $query->setLimitPage($pageNumber, $this->perPage);

        // No facets for the api.

        // Send the query to the search engine.
        /** @var \Search\Querier\QuerierInterface $querier */
        $querier = $index->querier();
        try {
            $searchResponse = $querier->query($query);
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

        $response = new Response($entities);
        $response->setTotalResults($totalResults);
        return $response;
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
        if (!$property) {
            return '';
        }

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
