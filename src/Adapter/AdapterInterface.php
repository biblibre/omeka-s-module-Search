<?php

/*
 * Copyright BibLibre, 2016-2017
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

namespace Search\Adapter;

use Search\Api\Representation\SearchIndexRepresentation;
use Zend\ServiceManager\ServiceLocatorInterface;

interface AdapterInterface
{
    /**
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator);

    /**
     * Get the name of the adapter.
     *
     * @return string
     */
    public function getLabel();

    /**
     * Return the form used to managed the config of the adapter, if any.
     *
     * @çeturn \Zend\Form\Fieldset|null
     */
    public function getConfigFieldset();

    /**
     * Get the fully qualified name of the indexer class used by this adapter.
     *
     * @return string
     */
    public function getIndexerClass();

    /**
     * Get the fully qualified name of the querier class used by this adapter.
     *
     * @return string
     */
    public function getQuerierClass();

    /**
     * Get the available fields.
     *
     * @param SearchIndexRepresentation $index
     * @return array Associative array with field name as key and an array with
     * field name and field label as value.
     */
    public function getAvailableFields(SearchIndexRepresentation $index);

    /**
     * Get the available sort fields.
     *
     * @param SearchIndexRepresentation $index
     * @return array Associative array with sort name as key and an array with
     * sort name and sort label as value.
     */
    public function getAvailableSortFields(SearchIndexRepresentation $index);

    /**
     * Get the available facet fields.
     *
     * @param SearchIndexRepresentation $index
     * @return array Associative array with facet name as key and an array with
     * facet name and facet label as value.
     */
    public function getAvailableFacetFields(SearchIndexRepresentation $index);
}
