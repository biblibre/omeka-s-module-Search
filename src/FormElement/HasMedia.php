<?php

namespace Search\FormElement;

use Laminas\View\Renderer\PhpRenderer;
use Laminas\Mvc\I18n\Translator;
use Search\Api\Representation\SearchPageRepresentation;
use Search\Feature\SummarizeQueryInterface;
use Search\Query;

class HasMedia implements SearchFormElementInterface, SummarizeQueryInterface
{
    protected Translator $translator;

    public function getLabel(): string
    {
        return 'Has media'; // @translate
    }

    public function getConfigForm(SearchPageRepresentation $searchPage, PhpRenderer $view, array $formElementData): string
    {
        return '';
    }

    public function isRepeatable(): bool
    {
        return false;
    }

    public function getForm(SearchPageRepresentation $searchPage, PhpRenderer $view, array $data, array $formElementData): string
    {
        $element = new \Search\Form\Element\OptionalSelect('has_media');
        $element
            ->setLabel('Search by media presence') // @translate
            ->setValueOptions([
                '1' => 'Has media', // @translate
                '0' => 'Has no media', // @translate
            ])
            ->setEmptyOption('Select media presence…') // @translate
            ->setValue($data['has_media'] ?? '')
            ->setAttribute('id', 'has_media');

        return $view->formRow($element);
    }

    public function applyToQuery(Query $query, array $data, array $formElementData): void
    {
        if (isset($data['has_media']) && is_numeric($data['has_media'])) {
            $query->setHasMedia($data['has_media'] ? true : false);
        }
    }

    public function setTranslator(Translator $translator): void
    {
        $this->translator = $translator;
    }

    public function getTranslator(): Translator
    {
        return $this->translator;
    }

    public function summarizeQuery(array $data, SearchPageRepresentation $searchPage): array
    {
        $summary = [];
        $translator = $this->getTranslator();

        if (isset($data['has_media']) && is_numeric($data['has_media'])) {
            $summary[] = [
                'name' => $translator->translate('Has media'),
                'value' => $data['has_media'] ? $translator->translate('Yes') : $translator->translate('No'),
            ];
        }

        return $summary;
    }
}
