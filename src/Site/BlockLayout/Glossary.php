<?php

namespace Search\Site\BlockLayout;

use Laminas\Form\Form;
use Laminas\Form\Element\Checkbox;
use Laminas\Form\Element\Hidden;
use Laminas\Form\Element\MultiCheckbox;
use Laminas\Form\Element\Select;
use Laminas\Form\Element\Text;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;

class Glossary extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'Glossary'; // @translate
    }

    public function form(PhpRenderer $view, SiteRepresentation $site, SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null)
    {
        $defaults = [
            'search_page' => '',
            'facet_field' => '',
            'page_facet_field' => '',
            'custom_query' => '',
            'letters_list_position' => ['before', 'after'],
            'display_letters' => '0',
            'display_total' => '0',

        ];

        $data = $block ? $block->data() + $defaults : $defaults;

        $form = new Form();

        $searchPages = $view->api()->search('search_pages')->getContent();
        $searchPagesValueOptions = [];
        foreach ($searchPages as $searchPage) {
            $searchPagesValueOptions[$searchPage->id()] = $searchPage->name();

            $searchIndex = $searchPage->index();
            $facetFields = $searchIndex->availableFacetFields();
            foreach ($facetFields as $facetField) {
                $value = sprintf('%d:%s', $searchPage->id(), $facetField['name']);
                $facetFieldOptions[] = [
                    'value' => $value,
                    'label' => $facetField['label'],
                ];
            }
        }

        $searchPageSelect = new Select('o:block[__blockIndex__][o:data][search_page]');
        $searchPageSelect->setLabel('Search page to use'); // @translate
        $searchPageSelect->setValueOptions($searchPagesValueOptions);
        $searchPageSelect->setValue($data['search_page']);
        $form->add($searchPageSelect);

        $facetFieldHidden = new Hidden('o:block[__blockIndex__][o:data][facet_field]');
        $facetFieldHidden->setValue($data['facet_field']);
        $form->add($facetFieldHidden);

        $pageFacetFieldSelect = new Select('o:block[__blockIndex__][o:data][page_facet_field]');
        $pageFacetFieldSelect->setLabel('Facet field to use'); // @translate
        $pageFacetFieldSelect->setEmptyOption('Select a facet field'); // @translate
        $pageFacetFieldSelect->setValueOptions($facetFieldOptions);
        $pageFacetFieldSelect->setValue($data['page_facet_field']);
        $form->add($pageFacetFieldSelect);

        $customQueryText = new Text('o:block[__blockIndex__][o:data][custom_query]');
        $customQueryText->setLabel('Custom query parameters'); // @translate
        $customQueryText->setValue($data['custom_query']);
        $form->add($customQueryText);

        $lettersListPositionMultiCheckbox = new MultiCheckbox('o:block[__blockIndex__][o:data][letters_list_position]');
        $lettersListPositionMultiCheckbox->setLabel('Position of index of letters'); // @translate
        $lettersListPositionMultiCheckbox->setValueOptions([
            'before' => 'Before', // @translate
            'after' => 'After', // @translate
        ]);
        $lettersListPositionMultiCheckbox->setValue($data['letters_list_position']);
        $form->add($lettersListPositionMultiCheckbox);

        $displayLettersCheckbox = new Checkbox('o:block[__blockIndex__][o:data][display_letters]');
        $displayLettersCheckbox->setLabel('Display letters between results'); // @translate
        $displayLettersCheckbox->setValue($data['display_letters']);
        $form->add($displayLettersCheckbox);

        $displayTotalCheckbox = new Checkbox('o:block[__blockIndex__][o:data][display_total]');
        $displayTotalCheckbox->setLabel('Display total between results'); // @translate
        $displayTotalCheckbox->setValue($data['display_total']);
        $form->add($displayTotalCheckbox);

        return $view->formCollection($form);
    }

    public function prepareForm(PhpRenderer $view)
    {
        $view->headScript()->appendFile($view->assetUrl('js/search-glossary-form.js', 'Search'));
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        $searchPageId = $block->dataValue('search_page');
        $customQueryInput = $block->dataValue('custom_query', '');
        $facetField = $block->dataValue('facet_field');

        try {
            $searchPage = $view->api()->read('search_pages', $searchPageId)->getContent();
        } catch (\Exception $e) {
            $view->logger()->err(sprintf('Search glossary block: Search page with id %s not found.', $searchPageId));
            return '';
        }

        try {
            $formAdapter = $searchPage->formAdapter();
        } catch (\Exception $e) {
            $formAdapterName = $searchPage->formAdapterName();
            $view->logger()->err(sprintf("Search glossary block: Form adapter '%s' not found", $formAdapterName));
            return '';
        }

        $searchPageSettings = $searchPage->settings();
        $searchFormSettings = $searchPageSettings['form'] ?? [];

        $searchIndex = $searchPage->index();
        $querier = $searchIndex->querier();

        parse_str($customQueryInput, $customQuery);

        $query = $formAdapter->toQuery($customQuery, $searchFormSettings);
        $query->addFacetField($facetField);
        $query->setFacetLimit($facetField, null);
        $query->setLimitPage(1, 0);
        $query->setResources($searchIndex->settings()['resources']);
        $query->setSite($view->currentSite());

        if (isset($customQuery['limit'])) {
            foreach ($customQuery['limit'] as $name => $values) {
                foreach ($values as $value) {
                    $query->addFacetFilter($name, $value);
                }
            }
        }

        $response = $querier->query($query);

        $termsByLetter = [];

        $facetCounts = $response->getFacetCounts();
        foreach ($facetCounts[$facetField] ?? [] as $facetCount) {
            $term = $facetCount['value'];
            $letter = mb_strtoupper(mb_substr($term, 0, 1));
            $termsByLetter[$letter] ??= [];
            $termsByLetter[$letter][] = $term;
        }

        if (extension_loaded('intl')) {
            // Use the Unicode Collation Algorithm (UCA) so that letters with
            // accents are ordered next to the same letter without accent,
            // instead of at the end
            $collator = new \Collator('root');
            uksort($termsByLetter, fn($a, $b) => $collator->compare($a, $b));
            foreach (array_keys($termsByLetter) as $letter) {
                usort($termsByLetter[$letter], fn ($a, $b) => $collator->compare($a, $b));
            }
        } else {
            ksort($termsByLetter);
            foreach (array_keys($termsByLetter) as $letter) {
                usort($termsByLetter[$letter], fn ($a, $b) => strcasecmp($a, $b));
            }
        }

        return $view->partial('search/block-layout/glossary', [
            'block' => $block,
            'searchPage' => $searchPage,
            'termsByLetter' => $termsByLetter,
        ]);
    }

    public function prepareRender(PhpRenderer $view): void
    {
        $view->headLink()->appendStylesheet($view->assetUrl('css/search-glossary.css', 'Search'));
    }
}
