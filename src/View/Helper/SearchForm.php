<?php

namespace Search\View\Helper;

use Search\Api\Representation\SearchPageRepresentation;
use Zend\Form\Form;
use Zend\View\Helper\AbstractHelper;

class SearchForm extends AbstractHelper
{
    /**
     * @var SearchPageRepresentation
     */
    protected $searchPage;

    /**
     * @var Form
     */
    protected $form;

    /**
     * @param SearchPageRepresentation $searchPage
     * @return \Search\View\Helper\SearchForm
     */
    public function __invoke(SearchPageRepresentation $searchPage = null)
    {
        // FIXME The view helper should not fail if it has not been initialized.
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

    /**
     * @return \Zend\Form\Form
     */
    public function getForm()
    {
        if (empty($this->form)) {
            $view = $this->getView();
            $this->form = $this->searchPage->form();
            $url = $view->params()->fromRoute('__ADMIN__')
                ? $view->setting('search_main_page')
                : $this->searchPage->url();
            $this->form->setAttribute('action', $url);
        }
        return $this->form;
    }

    /**
     * Get the partial form used for this form.
     *
     * @return string
     */
    protected function getFormPartial()
    {
        $formAdapter = $this->searchPage->formAdapter();
        $formPartial = $formAdapter->getFormPartial() ?: 'search/search-form';
        return $formPartial;
    }
}
