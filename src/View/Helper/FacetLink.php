<?php

namespace Search\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class FacetLink extends AbstractHelper
{
    public function __invoke($name, $facet)
    {
        $view = $this->getView();
        $query = $view->params()->fromQuery();

        $active = false;
        if (isset($query['limit'][$name]) && false !== array_search($facet['value'], $query['limit'][$name])) {
            $values = $query['limit'][$name];
            $values = array_filter($values, function ($v) use ($facet) {
                return $v != $facet['value'];
            });
            $query['limit'][$name] = $values;
            $active = true;
        } else {
            $query['limit'][$name][] = $facet['value'];
        }

        unset($query['page']);

        $url = $view->url(null, [], ['query' => $query], true);

        return $view->partial('search/facet-link', [
            'url' => $url,
            'active' => $active,
            'name' => $name,
            'value' => $facet['value'],
            'count' => $facet['count'],
        ]);
    }
}
