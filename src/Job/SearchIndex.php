<?php
/*
 * Copyright BibLibre, 2016
 * Copyright Daniel Berthereau, 2018
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
use Omeka\Stdlib\Message;

class SearchIndex extends AbstractJob
{
    const BATCH_SIZE = 100;

    /**
     * @var \Omeka\Mvc\Controller\Plugin\Logger
     */
    protected $logger;

    public function perform()
    {
        /**
         * @var \Omeka\Api\Manager $api
         * @var \Doctrine\ORM\EntityManager $em
         */
        $services = $this->getServiceLocator();
        $apiAdapters = $services->get('Omeka\ApiAdapterManager');
        $api = $services->get('Omeka\ApiManager');
        $em = $services->get('Omeka\EntityManager');
        $settings = $services->get('Omeka\Settings');
        $this->logger = $services->get('Omeka\Logger');

        $batchSize = (int) $settings->get('search_batch_size');
        if ($batchSize <= 0) {
            $batchSize = self::BATCH_SIZE;
        }

        $searchIndexId = $this->getArg('search_index_id');
        $startResourceId = (int) $this->getArg('start_resource_id');

        /** @var \Search\Api\Representation\SearchIndexRepresentation $searchIndex */
        $searchIndex = $api->read('search_indexes', $searchIndexId)->getContent();
        $indexer = $searchIndex->indexer();

        $timeStart = microtime(true);
        $this->logger->info(new Message('Search index #%d ("%s"): start of indexing', // @translate
            $searchIndex->id(), $searchIndex->name()));

        $indexer->setServiceLocator($services);
        $indexer->setLogger($this->logger);

        $totalJobs = $services->get('ControllerPluginManager')->get('totalJobs');
        $totalJobs = $totalJobs(self::class, true);
        if ($totalJobs > 1) {
            $force = $this->getArg('force');
            if ($force) {
                $this->logger->warn(new Message(
                    'There are already %d other jobs "Search Index". Slowdowns may occur on the site.', // @translate
                    $totalJobs - 1
                ));
            } else {
                $this->logger->err(new Message(
                    'Search index #%d ("%s"): end of indexing: there are already %d other jobs "Search Index" and the current one is not forced.', // @translate
                    $searchIndex->id(), $searchIndex->name(), $totalJobs - 1
                ));
                return;
            }
        }

        if ($startResourceId > 0) {
            $this->logger->info(new Message(
                'Search index is not cleared: search starts at resource #%d.', // @translate
                $startResourceId
            ));
        } else {
            $indexer->clearIndex();
        }

        $searchIndexSettings = $searchIndex->settings();
        $resourceNames = $searchIndexSettings['resources'];
        $selectedResourceNames = $this->getArg('resource_names', []);
        if ($selectedResourceNames) {
            $resourceNames = array_intersect($resourceNames, $selectedResourceNames);
        }
        $resourceNames = array_filter($resourceNames, function ($resourceName) use ($indexer) {
            return $indexer->canIndex($resourceName);
        });
        if (empty($resourceNames)) {
            $this->logger->warn(new Message(
                'Search index #%d ("%s"): the indexing ended: there is no resource type to index.', // @translate
                $searchIndex->id(), $searchIndex->name()
            ));
            return;
        }

        $resources = [];
        $totals = [];
        foreach ($resourceNames as $resourceName) {
            $totals[$resourceName] = 0;
            $page = 1;
            $entityClass = $apiAdapters->get($resourceName)->getEntityClass();
            $dql = "SELECT resource FROM $entityClass resource";
            if ($startResourceId) {
                $dql .= " WHERE resource.id >= $startResourceId";
            }
            $dql .= " ORDER BY resource.id";

            do {
                if ($this->shouldStop()) {
                    if (empty($resources)) {
                        $this->logger->warn(new Message('Search index #%d ("%s"): the indexing was stopped. Nothing was indexed.', // @translate
                            $searchIndex->id(), $searchIndex->name()));
                    } else {
                        $totalResults = [];
                        foreach ($resourceNames as $resourceName) {
                            $totalResults[] = new Message('%s: %d indexed', $resourceName, $totals[$resourceName]); // @translate
                        }
                        $resource = array_pop($resources);
                        $this->logger->warn(new Message(
                            'Search index #%d ("%s"): the indexing was stopped. Last indexed resource: %s #%d; %s. Execution time: %d seconds.', // @translate
                            $searchIndex->id(),
                            $searchIndex->name(),
                            $resource->getResourceName(),
                            $resource->getId(),
                            implode('; ', $totalResults),
                            (int) (microtime(true) - $timeStart
                        )));
                    }
                    return;
                }
                // TODO Use doctrine large iterable data-processing? See https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/batch-processing.html#iterating-large-results-for-data-processing
                $offset = $batchSize * ($page - 1);
                $q = $em
                    ->createQuery($dql)
                    ->setFirstResult($offset)
                    ->setMaxResults($batchSize);
                /** @var \Omeka\Entity\Resource[] $resources */
                $resources = $q->getResult();

                $indexer->indexResources($resources);

                ++$page;
                $totals[$resourceName] += count($resources);
                $em->clear();
            } while (count($resources) == $batchSize);
        }

        $totalResults = [];
        foreach ($resourceNames as $resourceName) {
            $totalResults[] = new Message('%s: %d indexed', $resourceName, $totals[$resourceName]); // @translate
        }
        $this->logger->info(new Message('Search index #%d ("%s"): end of indexing. %s. Execution time: %s seconds.', // @translate
            $searchIndex->id(), $searchIndex->name(), implode('; ', $totalResults), (int) (microtime(true) - $timeStart)
        ));
    }
}
