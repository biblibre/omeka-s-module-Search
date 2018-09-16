<?php
namespace Search\Service\ControllerPlugin;

use Interop\Container\ContainerInterface;
use Search\Mvc\Controller\Plugin\ApiSearch;
use Zend\ServiceManager\Factory\FactoryInterface;

class ApiSearchFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $api = $services->get('Omeka\ApiManager');

        $settings = $services->get('Omeka\Settings');
        $apiIndex = $settings->get('search_api_index');
        if ($apiIndex) {
            try {
                $index = $api->read('search_indexes', ['id' => $apiIndex])->getContent();
                $adapterManager = $services->get('Omeka\ApiAdapterManager');
                $formAdapter = $services->get('Search\FormAdapterManager')->get('api');
                $acl = $services->get('Omeka\Acl');
                $logger = $services->get('Omeka\Logger');
                $translator = $services->get('MvcTranslator');
                $entityManager = $services->get('Omeka\EntityManager');
                $perPage = $settings->get('pagination_per_page', \Omeka\Stdlib\Paginator::PER_PAGE);
            } catch (\Omeka\Api\Exception\NotFoundException $e) {
                $index = null;
                $adapterManager = null;
                $formAdapter = null;
                $acl = null;
                $logger = null;
                $translator = null;
                $entityManager = null;
                $perPage = null;
            }
        } else {
            $index = null;
            $adapterManager = null;
            $formAdapter = null;
            $acl = null;
            $logger = null;
            $translator = null;
            $entityManager = null;
            $perPage = null;
        }

        return new ApiSearch(
            $api,
            $index,
            $adapterManager,
            $formAdapter,
            $acl,
            $logger,
            $translator,
            $entityManager,
            $perPage
        );
    }
}
