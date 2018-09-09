<?php
namespace Search\Form;

use Zend\Form\Element;
use Zend\Form\Form;

class ConfigForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'search_batch_size',
            'type' => Element\Number::class,
            'options' => [
                'label' => 'Search batch size for reindexation', // @translate
                'info' => 'Default is 100, but it can be adapted according to your resource average size, your mapping and your architecture.', // @translate
            ],
            'attributes' => [
                'id' => 'search_batch_size',
                'min' => 1,
                'step' => 1,
            ],
        ]);
    }
}
