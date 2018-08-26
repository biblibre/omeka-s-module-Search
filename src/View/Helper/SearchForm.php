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
        if (isset($searchPage)) {
            $this->searchPage = $searchPage;
            $this->form = null;
        }
        return $this;
    }

    public function __toString()
    {
        $formPartial = $this->getFormPartial();
        if (empty($formPartial)) {
            return '';
        }
        $form = $this->getForm();
        if (empty($form)) {
            return '';
        }
        return $this->getView()->partial($formPartial, ['form' => $form]);
    }

    /**
     * @return \Zend\Form\Form|null
     */
    public function getForm()
    {
        if (empty($this->searchPage)) {
            return;
        }
        if (empty($this->form)) {
            $this->form = $this->searchPage->form();
            $url = $this->getView()->params()->fromRoute('__ADMIN__')
                ? $this->searchPage->adminSearchUrl()
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
        if (empty($this->searchPage)) {
            return '';
        }
        $formAdapter = $this->searchPage->formAdapter();
        $formPartial = $formAdapter->getFormPartial() ?: 'search/search-form';
        return $formPartial;
    }
}
