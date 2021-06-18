<?php

/*
 * Copyright BibLibre, 2016-2021
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

class Index extends AbstractJob
{
    const DEFAULT_BATCH_SIZE = 100;

    protected $logger;

    public function perform()
    {
        $serviceLocator = $this->getServiceLocator();
        $apiAdapters = $serviceLocator->get('Omeka\ApiAdapterManager');
        $api = $serviceLocator->get('Omeka\ApiManager');
        $em = $serviceLocator->get('Omeka\EntityManager');
        $this->logger = $serviceLocator->get('Omeka\Logger');

        $indexId = $this->getArg('index-id');
        $batchSize = $this->getArg('batch-size', self::DEFAULT_BATCH_SIZE);

        $this->logger->info('Start');
        $em->flush();

        $searchIndex = $api->read('search_indexes', $indexId)->getContent();
        $indexer = $searchIndex->indexer();
        $indexer->setServiceLocator($serviceLocator);
        $indexer->setLogger($this->logger);

        if ($this->getArg('clear-index')) {
            try {
                $indexer->clearIndex();
                $this->logger->info('The index has been cleared');
            } catch (\Exception $e) {
                $this->logger->err(sprintf('The attempt to clear the index has failed : %s', $e->getMessage()));
                $em->flush();
            }
        }

        $searchIndexSettings = $searchIndex->settings();
        $resourceNames = $searchIndexSettings['resources'];

        $resourceNames = array_filter($resourceNames, function ($resourceName) use ($indexer) {
            return $indexer->canIndex($resourceName);
        });

        foreach ($resourceNames as $resourceName) {
            $adapter = $apiAdapters->get($resourceName);
            $entityClass = $adapter->getEntityClass();
            $repository = $em->getRepository($entityClass);
            $totalEntities = $repository->count([]);
            $query = $repository->createQueryBuilder('r')
                ->where('r.id > :lastId')
                ->orderBy('r.id', 'ASC')
                ->setMaxResults($batchSize)
                ->getQuery();

            $query->setParameter('lastId', 0);
            $totalIndexed = 0;

            do {
                if ($this->shouldStop()) {
                    $this->logger->info('Job stopped');
                    $em->flush();
                    return;
                }

                $entities = $query->getResult();
                if (!empty($entities)) {
                    $ids = array_map(function ($e) {
                        return $e->getId();
                    }, $entities);
                    try {
                        $indexer->indexResources($entities);
                        $totalIndexed += count($entities);
                        $this->logger->info(sprintf('Indexed %d out of %d %s (%.2f%%)', $totalIndexed, $totalEntities, $resourceName, $totalIndexed * 100 / $totalEntities));
                    } catch (\Exception $e) {
                        $this->logger->err(sprintf('Indexing %s has failed: %s (ids: %s)', $resourceName, $e->getMessage(), implode(', ', $ids)));
                    }
                    $em->flush();
                    $em->clear();
                    $em->merge($this->job);

                    $query->setParameter('lastId', $ids[count($ids) - 1]);
                }
            } while (!empty($entities));
        }

        $this->logger->info('End');
        $em->flush();
    }
}
