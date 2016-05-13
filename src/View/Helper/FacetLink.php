<?php

namespace Search\View\Helper;

use Zend\View\Helper\AbstractHelper;

class FacetLink extends AbstractHelper
{
    public function __invoke($name, $facet)
    {
        $view = $this->getView();
        $serviceLocator = $view->getHelperPluginManager()->getServiceLocator();
        $mvcEvent = $serviceLocator->get('Application')->getMvcEvent();
        $routeMatch = $mvcEvent->getRouteMatch();
        $request = $mvcEvent->getRequest();

        $route = $routeMatch->getMatchedRouteName();
        $params = $routeMatch->getParams();
        $query = $request->getQuery()->toArray();

        $active = false;
        if (isset($query['limit'][$name]) && FALSE !== array_search($facet['value'], $query['limit'][$name])) {
            $values = $query['limit'][$name];
            $values = array_filter($values, function($v) use ($facet) {
                return $v != $facet['value'];
            });
            $query['limit'][$name] = $values;
            $active = true;
        } else {
            $query['limit'][$name][] = $facet['value'];
        }

        $url = $view->url($route, $params, ['query' => $query]);

        return $view->partial('search/facet-link', [
            'url' => $url,
            'active' => $active,
            'value' => $facet['value'],
            'count' => $facet['count'],
        ]);
    }
}
