<?php

namespace Search\FacetValueRenderer;

use Omeka\ServiceManager\AbstractPluginManager;

class Manager extends AbstractPluginManager
{
    protected $instanceOf = FacetValueRendererInterface::class;
}
