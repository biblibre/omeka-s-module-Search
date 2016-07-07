<?php

namespace Search\View\Helper;

use Zend\View\Helper\AbstractHelper;

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

    protected function getServiceLocator()
    {
        return $this->getView()->getHelperPluginManager()->getServiceLocator();
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
