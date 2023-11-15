<?php

namespace Search\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Search\FacetValueRenderer\Manager;

class SearchFacetValue extends AbstractHelper
{
    protected $facetValueRendererManager;

    public function __construct(Manager $facetValueRendererManager)
    {
        $this->facetValueRendererManager = $facetValueRendererManager;
    }

    public function __invoke(string $name, string $value): string
    {
        $facetValueRenderer = $this->getFacetValueRenderer($name);

        return $facetValueRenderer->render($this->getView(), $value);
    }

    protected function getFacetValueRenderer($name)
    {
        $view = $this->getView();
        $searchPage = $view->searchCurrentPage();
        $settings = $searchPage->settings();
        $facets = $settings['facets'] ?? [];

        $facetValueRendererName = null;
        foreach ($facets as $facet) {
            if ($facet['name'] === $name) {
                $facetValueRendererName = $facet['value_renderer'] ?? null;
                break;
            }
        }

        if (isset($facetValueRendererName)) {
            try {
                $facetValueRenderer = $this->facetValueRendererManager->get($facetValueRendererName);
            } catch (\Exception $e) {
                $facetValueRenderer = $this->facetValueRendererManager->get('fallback');
            }
        } else {
            $facetValueRenderer = $this->facetValueRendererManager->get('fallback');
        }

        return $facetValueRenderer;
    }
}
