<?php
namespace Search\View\Helper;

use Zend\View\Helper\AbstractHelper;

class SearchingForm extends AbstractHelper
{
    public function __invoke()
    {
        $view = $this->getView();

        // Check if the current site has a search form.
        $searchMainPage = $view->siteSetting('search_main_page');
        if ($searchMainPage) {
            $searchPage = $view->api()->searchOne('search_pages', ['id' => $searchMainPage])->getContent();
            if ($searchPage) {
                return $view->searchForm($searchPage);
            }
        }

        // Standard search form.
        $html = '<div id="search">';
        $html .= $view->partial('common/search-form');
        $html .= '</div>';
        return $html;
    }
}
