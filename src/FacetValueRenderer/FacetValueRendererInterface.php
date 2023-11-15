<?php

namespace Search\FacetValueRenderer;

use Laminas\View\Renderer\PhpRenderer;

interface FacetValueRendererInterface
{
    public function getLabel(): string;
    public function render(PhpRenderer $view, string $value): string;
}
