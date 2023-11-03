<?php

namespace Search\FacetValueRenderer;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Exception\NotFoundException;
use Omeka\Api\Manager as ApiManager;
use Omeka\ServiceManager\SortableInterface;

class ResourceTitle implements FacetValueRendererInterface, SortableInterface
{
    protected $api;

    public function __construct(ApiManager $api)
    {
        $this->api = $api;
    }

    public function getLabel(): string
    {
        return 'Resource title'; // @translate
    }

    public function render(PhpRenderer $view, string $value): string
    {
        if (!is_numeric($value)) {
            return $view->escapeHtml($value);
        }

        try {
            $resource = $this->api->read('resources', $value)->getContent();
        } catch (NotFoundException $e) {
            return $view->escapeHtml($value);
        }

        return $view->escapeHtml($resource->displayTitle());
    }

    public function getSortableString()
    {
        return $this->getLabel();
    }
}
