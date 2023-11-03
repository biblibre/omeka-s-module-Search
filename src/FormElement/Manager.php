<?php

namespace Search\FormElement;

use Omeka\ServiceManager\AbstractPluginManager;

class Manager extends AbstractPluginManager
{
    protected $instanceOf = SearchFormElementInterface::class;
}
