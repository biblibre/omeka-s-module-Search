<?php

/*
 * Copyright BibLibre, 2016
 * Copyright Daniel Berthereau, 2017-2018
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

namespace Search\Querier;

use Search\Api\Representation\SearchIndexRepresentation;
use Search\Query;
use Zend\Log\LoggerAwareTrait;
use Zend\ServiceManager\ServiceLocatorInterface;

abstract class AbstractQuerier implements QuerierInterface
{
    use LoggerAwareTrait;

    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * @param SearchIndexRepresentation $index
     */
    protected $index;

    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * @return \Zend\ServiceManager\ServiceLocatorInterface
     */
    protected function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    public function setSearchIndex(SearchIndexRepresentation $index)
    {
        $this->index = $index;
    }

    /**
     * Get a setting of the search index.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    protected function getSetting($name, $default = null)
    {
        $settings = $this->index->settings();

        if (isset($settings[$name])) {
            return $settings[$name];
        }

        return $default;
    }

    /**
     * Get a setting of the search adapter.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    protected function getAdapterSetting($name, $default = null)
    {
        $adapterSettings = $this->getSetting('adapter', []);

        if (isset($adapterSettings[$name])) {
            return $adapterSettings[$name];
        }

        return $default;
    }

    abstract public function query(Query $query);
}
