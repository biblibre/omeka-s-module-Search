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
use Transliterator;

class Glossary extends AbstractBlockLayout
{
    protected Transliterator $transliterator;

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
            'group_accented_letters' => '0',
            'section_id_prefix' => '',
            'section_heading' => '',
            'show_term_count' => '0',
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
        $searchPageSelect->setOption('info', "Glossary terms will be retrieved according to this page's settings (in particular which search index to use), and clicking on a term will redirect to this page"); // @translate
        $searchPageSelect->setValueOptions($searchPagesValueOptions);
        $searchPageSelect->setValue($data['search_page']);
        $form->add($searchPageSelect);

        $facetFieldHidden = new Hidden('o:block[__blockIndex__][o:data][facet_field]');
        $facetFieldHidden->setValue($data['facet_field']);
        $form->add($facetFieldHidden);

        $pageFacetFieldSelect = new Select('o:block[__blockIndex__][o:data][page_facet_field]');
        $pageFacetFieldSelect->setLabel('Facet field to use'); // @translate
        $pageFacetFieldSelect->setOption('info', "Glossary terms will be retrieved from this field. The list of available fields depends on the selected Search page above"); // @translate
        $pageFacetFieldSelect->setEmptyOption('Select a facet field'); // @translate
        $pageFacetFieldSelect->setValueOptions($facetFieldOptions);
        $pageFacetFieldSelect->setValue($data['page_facet_field']);
        $form->add($pageFacetFieldSelect);

        $customQueryText = new Text('o:block[__blockIndex__][o:data][custom_query]');
        $customQueryText->setLabel('Custom query parameters'); // @translate
        $customQueryText->setOption('info', 'URL parameters added to limit the list of terms. For instance: "limit[resource_template][]=Base Resource" (requires the Search adapter to expose a "resource_template" facet field)'); // @translate
        $customQueryText->setValue($data['custom_query']);
        $form->add($customQueryText);

        $lettersListPositionMultiCheckbox = new MultiCheckbox('o:block[__blockIndex__][o:data][letters_list_position]');
        $lettersListPositionMultiCheckbox->setLabel('Position of index of letters'); // @translate
        $lettersListPositionMultiCheckbox->setValueOptions([
            'before' => 'Before the term list', // @translate
            'after' => 'After the term list', // @translate
        ]);
        $lettersListPositionMultiCheckbox->setValue($data['letters_list_position']);
        $form->add($lettersListPositionMultiCheckbox);

        $groupAccentedLettersCheckbox = new Checkbox('o:block[__blockIndex__][o:data][group_accented_letters]');
        $groupAccentedLettersCheckbox->setLabel('Group accented letters'); // @translate
        $groupAccentedLettersCheckbox->setOption('info', 'If enabled, terms starting with "È" or "É" will be displayed under the letter "E" (requires PHP extension intl)'); // @translate
        $groupAccentedLettersCheckbox->setValue($data['group_accented_letters']);
        $form->add($groupAccentedLettersCheckbox);

        $sectionIdPrefixText = new Text('o:block[__blockIndex__][o:data][section_id_prefix]');
        $sectionIdPrefixText->setLabel('Section identifier prefix'); // @translate
        $sectionIdPrefixText->setOption('info', 'Useful if there several glossary blocks on the same page. This will appear in URL when clicking on a letter'); // @translate
        $sectionIdPrefixText->setValue($data['section_id_prefix']);
        $form->add($sectionIdPrefixText);

        $sectionHeadingSelect = new Select('o:block[__blockIndex__][o:data][section_heading]');
        $sectionHeadingSelect->setLabel('Section heading'); // @translate
        $sectionHeadingSelect->setEmptyOption('None'); // @translate
        $sectionHeadingSelect->setValueOptions([
            'letter' => 'Letter only', // @translate
            'letter_total' => 'Letter and number of terms', // @translate
        ]);
        $sectionHeadingSelect->setValue($data['section_heading']);
        $form->add($sectionHeadingSelect);

        $showTermCountCheckbox = new Checkbox('o:block[__blockIndex__][o:data][show_term_count]');
        $showTermCountCheckbox->setLabel('Show term count'); // @translate
        $showTermCountCheckbox->setOption('info', 'Show the number of resources for each term'); // @translate
        $showTermCountCheckbox->setValue($data['show_term_count']);
        $form->add($showTermCountCheckbox);

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
            $letter = $this->getFirstLetter($term, $block);
            $termsByLetter[$letter] ??= [];
            $termsByLetter[$letter][] = [
                'term' => $term,
                'count' => $facetCount['count'],
            ];
        }

        if (extension_loaded('intl')) {
            // Use the Unicode Collation Algorithm (UCA) so that letters with
            // accents are ordered next to the same letter without accent,
            // instead of at the end
            $collator = new \Collator('root');
            $compare = fn($a, $b) => $collator->compare($a, $b);
        } else {
            $compare = fn($a, $b) => strcasecmp($a, $b);
        }

        uksort($termsByLetter, fn($a, $b) => $compare($a, $b));
        foreach (array_keys($termsByLetter) as $letter) {
            usort($termsByLetter[$letter], fn ($a, $b) => $compare($a['term'], $b['term']));
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

    protected function getFirstLetter(string $term, SitePageBlockRepresentation $block): string
    {
        $letter = mb_substr($term, 0, 1);

        if ($block->dataValue('group_accented_letters') && extension_loaded('intl')) {
            if (!isset($this->transliterator)) {
                $this->transliterator = Transliterator::createFromRules(':: NFD; :: [:Nonspacing Mark:] Remove; :: Upper(); :: NFC;');
            }

            $letter = $this->transliterator->transliterate($letter);
        } else {
            $letter = mb_strtoupper($letter);
        }

        return $letter;
    }
}
