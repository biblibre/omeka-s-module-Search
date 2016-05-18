<?php

/*
 * Copyright BibLibre, 2016
 *
 * This software is governed by the CeCILL license under French law and abiding
 * by the rules of distribution of free software.  You can use, modify and/ or
 * redistribute the software under the terms of the CeCILL license as circulated
 * by CEA, CNRS and INRIA at the following URL "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and rights to copy, modify
 * and redistribute granted by the license, users are provided only with a
 * limited warranty and the software's author, the holder of the economic
 * rights, and the successive licensors have only limited liability.
 *
 * In this respect, the user's attention is drawn to the risks associated with
 * loading, using, modifying and/or developing or reproducing the software by
 * the user in light of its specific status of free software, that may mean that
 * it is complicated to manipulate, and that also therefore means that it is
 * reserved for developers and experienced professionals having in-depth
 * computer knowledge. Users are therefore encouraged to load and test the
 * software's suitability as regards their requirements in conditions enabling
 * the security of their systems and/or data to be ensured and, more generally,
 * to use and operate it in the same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
 */

namespace Search\Job;

use Omeka\Job\AbstractJob;
use Omeka\Log\Writer\Job as JobWriter;

class Index extends AbstractJob
{
    const BATCH_SIZE = 100;

    protected $logger;

    public function perform()
    {
        $serviceLocator = $this->getServiceLocator();
        $api = $serviceLocator->get('Omeka\ApiManager');
        $this->logger = $serviceLocator->get('Omeka\Logger');

        $indexId = $this->getArg('index-id');
        $this->logger->info('Start');
        $this->logger->info('Index id: ' . $indexId);

        $searchIndex = $api->read('search_indexes', $indexId)->getContent();
        $indexer = $searchIndex->indexer();
        $indexer->setServiceLocator($serviceLocator);
        $indexer->setLogger($this->logger);
        $indexer->setSearchIndex($searchIndex);

        $indexer->clearIndex();

        $searchIndexSettings = $searchIndex->settings();
        $resourceNames = $searchIndexSettings['resources'];

        foreach ($resourceNames as $resourceName) {
            $data = ['page' => 1, 'per_page' => self::BATCH_SIZE];
            do {
                $resources = $api->search($resourceName, $data)->getContent();
                $indexer->indexResources($resources);
                $data['page']++;
            } while (count($resources) == self::BATCH_SIZE);
        }

        $this->logger->info('End');
    }
}
