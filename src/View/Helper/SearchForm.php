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

        $enabledSearchFields = array_column($settings['form']['search_fields'] ?? [], 'label', 'name');
        $availableSearchFields = [];
        if (!empty($enabledSearchFields)) {
            foreach ($index->availableSearchFields() as $searchField) {
                $name = $searchField['name'];
                if (array_key_exists($name, $enabledSearchFields)) {
                    $label = $enabledSearchFields[$name] ?? '';
                    if (!empty($label)) {
                        $searchField['label'] = $label;
                    }
                    $availableSearchFields[] = $searchField;
                }
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
