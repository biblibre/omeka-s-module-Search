<?php

namespace Search\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Search\Api\Representation\SearchPageRepresentation;
use Search\FormElement\Manager;

class SearchFormElement extends AbstractHelper
{
    protected $searchFormElementManager;

    public function __construct(Manager $searchFormElementManager)
    {
        $this->searchFormElementManager = $searchFormElementManager;
    }

    public function label(array $formElementData): string
    {
        $name = $formElementData['name'];
        $formElement = $this->searchFormElementManager->get($name);

        return $formElement->getLabel();
    }

    public function form(SearchPageRepresentation $searchPage, array $data, array $formElementData): string
    {
        $name = $formElementData['name'];
        $formElement = $this->searchFormElementManager->get($name);

        return $formElement->getForm($searchPage, $this->getView(), $data, $formElementData);
    }

    public function configForm(SearchPageRepresentation $searchPage, array $formElementData): string
    {
        $name = $formElementData['name'];
        $formElement = $this->searchFormElementManager->get($name);

        return $formElement->getConfigForm($searchPage, $this->getView(), $formElementData);
    }
}
