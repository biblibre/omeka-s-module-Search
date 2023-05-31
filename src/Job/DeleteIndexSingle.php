<?php

namespace Search\Job;

use Omeka\Job\AbstractJob;

class DeleteIndexSingle extends AbstractJob
{
    public function perform()
    {
        $serviceLocator = $this->getServiceLocator();
        $api = $serviceLocator->get('Omeka\ApiManager');
        $logger = $serviceLocator->get('Omeka\Logger');

        $searchIndexes = $api->search('search_indexes')->getContent();
        foreach ($searchIndexes as $searchIndex) {
            $searchIndexSettings = $searchIndex->settings();
            if (in_array($this->getArg('type'), $searchIndexSettings['resources'])) {
                $indexer = $searchIndex->indexer();
                try {
                    $indexer->deleteResource($this->getArg('type'), $this->getArg('id'));
                } catch (\Exception $e) {
                    $logger->err(sprintf('Search: failed to delete resource: %s', $e));
                }
            }
        }
    }
}
