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
        $apiPage = $settings->get('search_api_page');
        if ($apiPage) {
            try {
                /** @var \Search\Api\Representation\SearchPageRepresentation $page */
                $page = $api->read('search_pages', ['id' => $apiPage])->getContent();
                $index = $page->index();
            } catch (\Omeka\Api\Exception\NotFoundException $e) {
            }
            if ($index) {
                $adapterManager = $services->get('Omeka\ApiAdapterManager');
                $formAdapter = $services->get('Search\FormAdapterManager')->get('api');
                $acl = $services->get('Omeka\Acl');
                $logger = $services->get('Omeka\Logger');
                $translator = $services->get('MvcTranslator');
                $entityManager = $services->get('Omeka\EntityManager');
                $paginator = $services->get('Omeka\Paginator');
                return new ApiSearch(
                    $api,
                    $page,
                    $index,
                    $adapterManager,
                    $formAdapter,
                    $acl,
                    $logger,
                    $translator,
                    $entityManager,
                    $paginator
                );
            }
        }
        return new ApiSearch($api);
    }
}
