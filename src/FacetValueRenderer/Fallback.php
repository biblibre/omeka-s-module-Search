<?php

namespace Search\FacetValueRenderer;

use Laminas\View\Renderer\PhpRenderer;

class Fallback implements FacetValueRendererInterface
{
    public function getLabel(): string
    {
        return 'Fallback'; // @translate
    }

    public function render(PhpRenderer $view, string $value): string
    {
        return $view->escapeHtml($value);
    }
}
