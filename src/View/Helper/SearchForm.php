<?php

namespace Search\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class SearchForm extends AbstractHelper
{
    protected $searchPage;
    protected $form;

    public function __invoke($searchPage = null)
    {
        if (isset($searchPage)) {
            $this->searchPage = $searchPage;
            $this->form = null;
        }

        return $this;
    }

    public function __toString()
    {
        $view = $this->getView();

        $formPartial = $this->getFormPartial();
        $form = $this->getForm();

        return $view->partial($formPartial, ['form' => $form]);
    }

    public function getForm()
    {
        if (!isset($this->form)) {
            $this->form = $this->searchPage->form();
            $this->form->setAttribute('action', $this->searchPage->url());
        }

        return $this->form;
    }

    public function getAvailableSearchFields()
    {
        $index = $this->searchPage->index();
        $settings = $this->searchPage->settings();

        $indexAvailableSearchFields = array_column($index->availableSearchFields(), null, 'name');
        $availableSearchFields = [];
        foreach ($settings['form']['search_fields'] ?? [] as $searchField) {
            $name = $searchField['name'];
            if (array_key_exists($name, $indexAvailableSearchFields)) {
                $indexSearchField = $indexAvailableSearchFields[$name];
                if (!empty($searchField['label'])) {
                    $indexSearchField['label'] = $searchField['label'];
                }
                $availableSearchFields[] = $indexSearchField;
            }
        }

        return $availableSearchFields;
    }

    public function getAvailableOperators()
    {
        $index = $this->searchPage->index();
        $adapter = $index->adapter();

        return $adapter->getAvailableOperators($index);
    }

    protected function getFormPartial()
    {
        $formAdapter = $this->searchPage->formAdapter();

        $formPartial = $formAdapter->getFormPartial();
        if (!isset($formPartial)) {
            $formPartial = 'search/search-form';
        }

        return $formPartial;
    }
}
