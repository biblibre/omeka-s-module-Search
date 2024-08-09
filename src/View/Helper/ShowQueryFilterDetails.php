<?php

namespace Search\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class ShowQueryFilterDetails extends AbstractHelper
{
    public function __invoke($filters)
    {
        $field = $filters['field'];
        $operator = $filters['operator'];
        $term = $filters['term'];
        $view = $this->getView();

        return $view->partial('search/show-query-filter-details', [
            'field' => $field,
            'operator' => $operator,
            'term' => $term,
        ]);
    }
}
