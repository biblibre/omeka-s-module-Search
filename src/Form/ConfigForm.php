<?php

namespace Search\Form;

use Laminas\Form\Form;

class ConfigForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'search_check_interval',
            'type' => 'Text',
            'options' => [
                'label' => 'Check interval (seconds)', // @translate
                'info' => 'The frequency at which the module checks if there are resources to be indexed. A smaller interval means a more up-to-date index. This should only be used if the script `bin/index` cannot be run periodically (using cron for instance). Leave empty to disable.', // @translate
            ],
            'attributes' => [
                'id' => 'search-check-interval',
            ],
        ]);
    }
}
