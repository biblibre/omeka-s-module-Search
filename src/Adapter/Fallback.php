<?php

namespace Search\Adapter;

use Laminas\Form\Fieldset;
use Omeka\Stdlib\Message;

class Fallback extends AbstractAdapter
{
    public function __construct(protected string $name)
    {
    }

    public function getLabel()
    {
        $message = new Message(
            'Unknown [%s]', // @translate
            $this->name
        );

        return $message;
    }

    public function getConfigFieldset()
    {
        return new Fieldset();
    }

    public function getIndexerClass()
    {
        return null;
    }

    public function getQuerierClass()
    {
        return null;
    }
}
