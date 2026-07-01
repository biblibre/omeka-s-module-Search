<?php

namespace Search\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class ActiveFacetLink extends AbstractHelper
{
    public function __invoke($name, $value)
    {
        $view = $this->getView();
        $query = $view->params()->fromQuery();

        $removeQuery = $query;
        $removeQuery['limit'][$name] = array_values(array_filter(
            $removeQuery['limit'][$name],
            fn ($v) => $v !== $value
        ));
        if (empty($removeQuery['limit'][$name])) {
            unset($removeQuery['limit'][$name]);
        }
        if (empty($removeQuery['limit'])) {
            unset($removeQuery['limit']);
        }
        unset($removeQuery['page']);

        $url = $view->url(null, [], ['query' => $removeQuery], true);

        return $view->partial('search/active-facet-link', [
            'url' => $url,
            'name' => $name,
            'value' => $value,
        ]);
    }
}
