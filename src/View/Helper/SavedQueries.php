<?php

namespace Search\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class SavedQueries extends AbstractHelper
{
    public function __invoke($savedQueries = null)
    {
        $view = $this->getView();
        $user = $view->identity();
        if (isset($user)) {
            $savedQueries = $view->api()->search('saved_queries', ['user_id' => $user->getId()])->getContent();

            return $view->partial('search/saved-queries', [
            'savedQueries' => $savedQueries,
        ]);
        }
    }
}
