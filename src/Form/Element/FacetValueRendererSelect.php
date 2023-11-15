<?php

namespace Search\Form\Element;

use Laminas\Form\Element\Select;
use Search\FacetValueRenderer\Manager as FacetValueRendererManager;

class FacetValueRendererSelect extends Select
{
    protected $facetValueRendererManager;

    public function setFacetValueRendererManager(FacetValueRendererManager $facetValueRendererManager)
    {
        $this->facetValueRendererManager = $facetValueRendererManager;
    }

    public function getValueOptions(): array
    {
        $valueOptions = [];

        $names = $this->facetValueRendererManager->getRegisteredNames($sortAlpha = true);
        foreach ($names as $name) {
            if ($name === 'fallback') {
                continue;
            }

            try {
                $facetValueRenderer = $this->facetValueRendererManager->get($name);
                $valueOptions[$name] = $facetValueRenderer->getLabel();
            } catch (\Exception $e) {
                $valueOptions[$name] = $name;
            }
        }

        return $valueOptions;
    }
}
