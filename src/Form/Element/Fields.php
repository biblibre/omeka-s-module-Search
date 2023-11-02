<?php
namespace Search\Form\Element;

use Laminas\Filter\Callback;
use Laminas\Form\Element;
use Laminas\InputFilter\InputProviderInterface;

class Fields extends Element implements InputProviderInterface
{
    protected $attributes = [
        'class' => 'fields-fields-data',
    ];

    public function getInputSpecification()
    {
        return [
            'required' => false,
            'filters' => [
                // Decode JSON into a PHP array so data can be stored properly.
                new Callback(function ($json) {
                    return json_decode($json, true);
                }),
            ],
        ];
    }
}
