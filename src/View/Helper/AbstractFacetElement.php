<?php

namespace Search\View\Helper;

use Zend\Mvc\Application;
use Zend\View\Helper\AbstractHelper;

class AbstractFacetElement extends AbstractHelper
{
    /**
     * @var string
     */
    protected $partial;

    /**
     * @param Application $application
     */
    protected $application;

    /**
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * Create one facet link.
     *
     * @param string $name
     * @param string $facet
     * @return string
     */
    public function __invoke($name, $facet)
    {
        // Variables are static to speed up process.
        static $urlHelper;
        static $partialHelper;
        static $escapeHtml;

        static $mvcEvent;
        static $routeMatch;
        static $request;

        static $route;
        static $params;
        static $queryBase;

        if (is_null($mvcEvent)) {
            $plugins = $this->getView()->getHelperPluginManager();
            $urlHelper = $plugins->get('url');
            $partialHelper = $plugins->get('partial');
            $escapeHtml = $plugins->get('escapeHtml');

            $mvcEvent = $this->application->getMvcEvent();
            $routeMatch = $mvcEvent->getRouteMatch();
            $request = $mvcEvent->getRequest();

            $route = $routeMatch->getMatchedRouteName();
            $params = $routeMatch->getParams();
            $queryBase = $request->getQuery()->toArray();

            unset($queryBase['page']);
        }

        $query = $queryBase;

        if (isset($query['limit'][$name]) && array_search($facet['value'], $query['limit'][$name]) !== false) {
            $values = $query['limit'][$name];
            $values = array_filter($values, function ($v) use ($facet) {
                return $v != $facet['value'];
            });
            $query['limit'][$name] = $values;
            $active = true;
        } else {
            $query['limit'][$name][] = $facet['value'];
            $active = false;
        }

        return $partialHelper($this->partial, [
            'name' => $name,
            'value' => $facet['value'],
            'url' => $urlHelper($route, $params, ['query' => $query]),
            'active' => $active,
            'count' => $facet['count'],
            // To speed up process.
            'escapeHtml' => $escapeHtml,
        ]);
    }
}
