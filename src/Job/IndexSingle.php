<?php

namespace Search\Job;

use Omeka\Job\AbstractJob;

class IndexSingle extends AbstractJob
{
    public function perform()
    {
        $serviceLocator = $this->getServiceLocator();
        $api = $serviceLocator->get('Omeka\ApiManager');
        $em = $serviceLocator->get('Omeka\EntityManager');
        $logger = $serviceLocator->get('Omeka\Logger');
        
        $resourceDetail = $this->getArg('resource_detail');

        $resource = $em->find('Omeka\Entity\Resource', $resourceDetail['id']);

        $searchIndexes = $api->search('search_indexes')->getContent();
        foreach ($searchIndexes as $searchIndex) {
            $searchIndexSettings = $searchIndex->settings();
            if (in_array($resourceDetail['type'], $searchIndexSettings['resources'])) {
                $indexer = $searchIndex->indexer();

                if ($resourceDetail['operation'] == 'delete') {
                    try {
                        $indexer->deleteResource($resourceDetail['type'], $resourceDetail['id']);
                    } catch (\Exception $e) {
                        $logger->err(sprintf('Search: failed to delete resource: %s', $e));
                    }
                } else {
                    try {
                        $indexer->indexResource($resource);
                    } catch (\Exception $e) {
                        $logger->err(sprintf('Search: failed to index resource: %s', $e));
                    }
                }
            }
        }
    }
}
