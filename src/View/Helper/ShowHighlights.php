<?php

namespace Search\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class ShowHighlights extends AbstractHelper
{
    public function __invoke($field, array $extracts)
    {
        $view = $this->getView();
        $totalExtracts = count($extracts);
        $limit = 3;
        return $view->partial('search/highlights-extracts', [
            'field' => $field,
            'extracts' => $extracts,
            'totalExtracts' => $totalExtracts,
            'limit' => $limit,
        ]);
    }
}
