<?php

namespace Search\View\Helper;

use Zend\View\Helper\AbstractHelper;

class SearchForm extends AbstractHelper
{
    public function __invoke($searchPage)
    {
        $view = $this->getView();

        $formPartial = $this->getFormPartial($searchPage);
        $form = $this->getForm($searchPage);

        return $view->partial($formPartial, ['form' => $form]);
    }

    protected function getServiceLocator()
    {
        return $this->getView()->getHelperPluginManager()->getServiceLocator();
    }

    protected function getFormPartial($searchPage)
    {
        $formAdapter = $searchPage->formAdapter();

        $formPartial = $formAdapter->getFormPartial();
        if (!isset($formPartial)) {
            $formPartial = 'search/search-form';
        }

        return $formPartial;
    }

    public function getForm($searchPage)
    {
        $form = $searchPage->form();
        $form->setAttribute('action', $searchPage->url());

        return $form;
    }
}
