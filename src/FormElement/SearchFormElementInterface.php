<?php

namespace Search\FormElement;

use Laminas\View\Renderer\PhpRenderer;
use Search\Api\Representation\SearchPageRepresentation;
use Search\Query;

interface SearchFormElementInterface
{
    public function getLabel(): string;

    public function getConfigForm(SearchPageRepresentation $searchPage, PhpRenderer $view, array $formElementData): string;

    public function isRepeatable(): bool;

    public function getForm(SearchPageRepresentation $searchPage, PhpRenderer $view, array $data, array $formElementData): string;

    public function applyToQuery(Query $query, array $data, array $formElementData): void;

    public function stringifyData(array $data, array $formElementData) : string;
}
