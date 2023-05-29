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

        $resource = $em->find('Omeka\Entity\Resource', $this->getArg('id'));
        $searchIndexes = $api->search('search_indexes')->getContent();
        foreach ($searchIndexes as $searchIndex) {
            $searchIndexSettings = $searchIndex->settings();
            if (in_array($resource->getResourceName(), $searchIndexSettings['resources'])) {
                $indexer = $searchIndex->indexer();
                try {
                    $indexer->indexResource($resource);
                } catch (\Exception $e) {
                    $logger->err(sprintf('Search: failed to index resource: %s', $e));
                }
            }
        }
    }
}
