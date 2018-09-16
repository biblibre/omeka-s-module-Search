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

namespace Search\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class SearchIndexRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLdType()
    {
        return 'o:SearchIndex';
    }

    public function getJsonLd()
    {
        $entity = $this->resource;
        return [
            'o:name' => $entity->getName(),
            'o:adapter' => $entity->getAdapter(),
            'o:settings' => $entity->getSettings(),
            'o:created' => $this->getDateTime($entity->getCreated()),
        ];
    }

    public function adminUrl($action = null, $canonical = false)
    {
        $url = $this->getViewHelper('Url');
        $params = [
            'action' => $action,
            'id' => $this->id(),
        ];
        $options = [
            'force_canonical' => $canonical,
        ];

        return $url('admin/search/index-id', $params, $options);
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->resource->getName();
    }

    /**
     * @return \Search\Adapter\AdapterInterface|null
     */
    public function adapter()
    {
        $name = $this->resource->getAdapter();
        $adapterManager = $this->getServiceLocator()->get('Search\AdapterManager');
        return $adapterManager->has($name)
            ? $adapterManager->get($name)
            : null;
    }

    /**
     * @return array
     */
    public function settings()
    {
        return $this->resource->getSettings();
    }

    /**
     * @return \DateTime
     */
    public function created()
    {
        return $this->resource->getCreated();
    }

    /**
     * @return \Search\Entity\SearchIndex
     */
    public function getEntity()
    {
        return $this->resource;
    }

    /**
     * @return string
     */
    public function adapterLabel()
    {
        $adapter = $this->adapter();
        if (!$adapter) {
            $translator = $this->getServiceLocator()->get('MvcTranslator');
            return sprintf($translator->translate('[Missing adapter "%s"]'), // @translate
                $this->resource->getAdapter()
            );
        }

        return $adapter->getLabel();
    }

    /**
     * @return \Search\Indexer\IndexerInterface|null
     */
    public function indexer()
    {
        $serviceLocator = $this->getServiceLocator();
        $indexerClass = $this->adapter()->getIndexerClass();
        if (!$indexerClass) {
            return null;
        }

        $indexer = new $indexerClass;
        $indexer->setSearchIndex($this);
        $indexer->setServiceLocator($serviceLocator);
        $indexer->setLogger($serviceLocator->get('Omeka\Logger'));

        return $indexer;
    }

    /**
     * @return \Search\Querier\QuerierInterface
     */
    public function querier()
    {
        $serviceLocator = $this->getServiceLocator();
        $querierClass = $this->adapter()->getQuerierClass();

        $querier = new $querierClass;
        $querier->setServiceLocator($serviceLocator);
        $querier->setLogger($serviceLocator->get('Omeka\Logger'));
        $querier->setIndex($this);

        return $querier;
    }
}
