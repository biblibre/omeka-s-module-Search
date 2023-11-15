<?php
namespace Search\Controller\Admin;

use Laminas\View\Model\ViewModel;
use Laminas\Mvc\Controller\AbstractActionController;
use Search\Form\FacetForm;

class FacetsController extends AbstractActionController
{
    public function fieldListAction()
    {
        $searchPageId = $this->params()->fromQuery('search_page_id');
        $searchPage = $this->api()->read('search_pages', $searchPageId)->getContent();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('searchPage', $searchPage);

        return $view;
    }

    public function fieldRowAction()
    {
        $searchPageId = $this->params()->fromQuery('search_page_id');
        $fieldData = $this->params()->fromQuery('field_data');

        $searchPage = $this->api()->read('search_pages', $searchPageId)->getContent();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('searchPage', $searchPage);
        $view->setVariable('fieldData', $fieldData);

        return $view;
    }

    public function fieldEditSidebarAction()
    {
        $fieldData = $this->params()->fromQuery('field_data');
        $form = $this->getForm(FacetForm::class);
        $form->setData($fieldData);

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('form', $form);

        return $view;
    }
}
