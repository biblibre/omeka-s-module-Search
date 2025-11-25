<?php

namespace Search\Job;

use DateTime;
use PDO;
use Doctrine\DBAL\Connection;
use Omeka\Job\AbstractJob;

class Sync extends AbstractJob
{
    const DEFAULT_BATCH_SIZE = 100;

    public function perform()
    {
        $services = $this->getServiceLocator();
        $api = $services->get('Omeka\ApiManager');
        $em = $services->get('Omeka\EntityManager');
        $logger = $services->get('Omeka\Logger');
        $indexationService = $services->get('Search\IndexationService');

        $acl = $services->get('Omeka\Acl');
        if (!$acl->userIsAllowed('Omeka\Entity\Resource', 'view-all')) {
            throw new \Exception('Search: Job owner is not allowed to view all resources');
        }

        $batch_size = $this->getArg('batch_size', self::DEFAULT_BATCH_SIZE);
        $max_execution_time = $this->getArg('max_execution_time');
        $start = time();

        $search_indexes = $api->search('search_indexes')->getContent();
        $search_indexes_by_id = [];
        foreach ($search_indexes as $search_index) {
            $search_indexes_by_id[$search_index->id()] = $search_index;
        }

        $number_of_resources_processed = 0;
        $search_resources = $indexationService->lockAndFetchResources(['limit' => $batch_size]);

        while ($search_resources) {
            $now = new DateTime();

            $resource_ids = array_map(fn($r) => $r['resource_id'], $search_resources);
            $resource_ids = array_values(array_unique($resource_ids, SORT_NUMERIC));
            $resources = $em->getRepository('Omeka\Entity\Resource')->findBy(['id' => $resource_ids]);
            $resources_by_id = [];
            foreach ($resources as $resource) {
                $resources_by_id[$resource->getId()] = $resource;
            }

            $resources_by_index_id = [];
            foreach ($search_resources as $search_resource) {
                $index_id = $search_resource['index_id'];
                $resource_id = $search_resource['resource_id'];
                if (isset($resources_by_id[$resource_id])) {
                    $resources_by_index_id[$index_id] ??= [];
                    $resources_by_index_id[$index_id][] = $resources_by_id[$resource_id];
                }
            }

            foreach ($resources_by_index_id as $index_id => $resources) {
                $search_index = $search_indexes_by_id[$index_id];
                $search_index_settings = $search_index->settings();

                $filtered_resources = array_filter(
                    $resources,
                    fn ($resource) => in_array($resource->getResourceName(), $search_index_settings['resources'] ?? [])
                );
                if (empty($filtered_resources)) {
                    continue;
                }

                try {
                    $indexer = $search_index->indexer();
                    $indexer->indexResources($filtered_resources);
                } catch (\Exception $e) {
                    $filtered_resources_ids = array_map(fn($r) => $r->getId(), $filtered_resources);
                    $filtered_resources_ids_string = implode(',', $filtered_resources_ids);
                    $logger->err(sprintf('Search: Failed to index resources in index %d (%s): %s', $index_id, $filtered_resources_ids_string, $e));
                }
            }

            $search_resource_ids = array_map(fn($r) => $r['id'], $search_resources);
            $indexationService->unlockAndMarkAsIndexedResources($search_resource_ids, $now);

            $number_of_resources_processed += count($search_resources);

            if ($max_execution_time && time() - $start >= $max_execution_time) {
                $logger->info("Search: Maximum execution time reached");
                break;
            }

            if ($this->shouldStop()) {
                $logger->info("Search: Job stopped");
                break;
            }

            $search_resources = $indexationService->lockAndFetchResources(['limit' => $batch_size]);
        }

        $logger->info(sprintf('Search: Processed a total of %d resources', $number_of_resources_processed));
    }
}
