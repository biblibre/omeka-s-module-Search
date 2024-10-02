<?php

/*
 * Copyright BibLibre, 2020
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

namespace Search\FormAdapter;

use Search\Feature\SummarizeQueryInterface;
use Search\FormElement\Manager as SearchFormElementManager;
use Search\Query;

class StandardFormAdapter implements FormAdapterInterface, SummarizeQueryInterface
{
    protected $apiManager;
    protected $translator;

    protected $searchFormElementManager;

    public function getLabel()
    {
        return 'Standard';
    }

    public function getFormClass()
    {
        return 'Search\Form\StandardForm';
    }

    public function getFormPartial()
    {
        return 'search/form/standard';
    }

    public function getConfigFormClass()
    {
        return 'Search\Form\StandardConfigForm';
    }

    public function toQuery($data, $formSettings)
    {
        $query = new Query();

        if (!empty($data['q'])) {
            $query->setQuery($data['q']);
        }

        if (!empty($data['filters'])) {
            $query->addQueryFilter($data['filters']);
        }

        foreach ($formSettings['elements'] ?? [] as $formElementData) {
            $name = $formElementData['name'];
            $formElement = $this->searchFormElementManager->get($name);
            $formElement->applyToQuery($query, $data, $formElementData);
        }

        return $query;
    }

    public function summarizeQuery($data, $page) : array
    {
        $summarizeQuery = [];
        $formSettings = $page->settings()['form'];

        if (!empty($data['q'])) {
            $summarizeQuery[] = ['name' => 'main_query', 'value' => $data['q']];
        }

        if (!empty($data['filters']['queries'])) {
            $filters = $this->summarizeFilters($data['filters']['queries'], $page, $data['filters']['match']);
            if (!empty($filters)) {
                $summarizeQuery[] = ['name' => 'filters_subqueries', 'value' => $filters];
            }
        }

        foreach ($formSettings['elements'] ?? [] as $formElementData) {
            $name = $formElementData['name'];
            $formElement = $this->searchFormElementManager->get($name);
            $summarizeQuery[] = $formElement->summarizeQuery($data, $page);
        }

        return $summarizeQuery;
    }

    public function summarizeFilters($queries, $page, $match = 'all')
    {
        $filters = [];
        $formSettings = $page->settings()['form'];
        $index = $page->index();
        $translator = $this->getTranslator();

        foreach ($queries as $query) {
            if (!empty($query['queries'])) {
                $filters[] = $this->summarizeFilters($query['queries'], $formSettings, $query['match']);
            } elseif (!empty($query['term'])) {
                $label = $this->getLabelForField($query['field'], $formSettings);
                $operator = $page->index()->availableOperators($index)[$query['operator']];
                $filters[] = sprintf("%s %s '%s'", $label, $operator['display_name'], $query['term']);
            }
        }

        $separator = ($match === 'all') ? ' AND ' : ' OR ';
        $separator = $translator->translate($separator);
        return count($filters) > 0 ? '(' . implode($separator, $filters) . ')' : '';
    }

    public function getLabelForField($fieldName, $formSettings)
    {
        foreach ($formSettings['search_fields'] as $field) {
            if ($field['name'] === $fieldName) {
                return $field['label'];
            }
        }
        return $fieldName;
    }

    public function setApiManager($apiManager)
    {
        $this->apiManager = $apiManager;
    }

    public function getApiManager()
    {
        return $this->apiManager;
    }

    public function setSearchFormElementManager(SearchFormElementManager $searchFormElementManager): void
    {
        $this->searchFormElementManager = $searchFormElementManager;
    }

    public function setTranslator($translator)
    {
        $this->translator = $translator;
    }

    public function getTranslator()
    {
        return $this->translator;
    }
}
