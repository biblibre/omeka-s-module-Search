<?php

namespace Search\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class ShowQuery extends AbstractHelper
{
    public function __invoke($queryFilters)
    {
        $view = $this->getView();
        return $view->partial('search/show-query', [
            'queryFilters' => $queryFilters,
        ]);
    }
}
