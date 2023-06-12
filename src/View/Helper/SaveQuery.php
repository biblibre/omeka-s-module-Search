<?php

namespace Search\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Search\Form\SaveQueryForm;

class SaveQuery extends AbstractHelper
{
    /**
     * @var saveQueryForm
     */
    protected $saveQueryForm;

    public function __invoke(array $params)
    {
        $view = $this->getView();
        $user = $view->identity();
        if (isset($user)) {
            $form = $this->saveQueryForm;
            $form->setData(['site_id' => $params['site_id'], 'search_page_id' => $params['search_page_id'], 'query_string' => $params['query_string']]);

            return $view->partial('search/save-query-form', [
                'form' => $form,
            ]);
        }
    }

    public function setSaveQueryForm(SaveQueryForm $saveQueryForm): void
    {
        $this->saveQueryForm = $saveQueryForm;
    }
}
