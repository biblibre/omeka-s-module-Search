<?php
namespace Search\Form\Admin;

use Omeka\Api\Manager as ApiManager;
use Search\Form\Element\OptionalSelect;
// use Zend\Form\Element;
use Zend\Form\Fieldset;

class ApiFormConfigFieldset extends Fieldset
{
    /**
     * @var ApiManager
     */
    protected $api;

    public function init()
    {
        $fieldOptions = $this->getFieldsOptions();

        $propertiesFieldset = new Fieldset('properties');
        $propertiesFieldset->setLabel('Mapping properties to search fields'); // @translate
        $propertiesFieldset->setAttribute('id', 'properties');

        /** @var \Omeka\Api\Representation\PropertyRepresentation[] $properties */
        $properties = $this->getApiManager()->search('properties')->getContent();

        foreach ($properties as $property) {
            $propertiesFieldset->add([
                'name' => $property->term(),
                // Input filter is available only by the form, not the fieldset.
                // It creates validation issues, so use an optional select.
                // 'type' => Element\Select::class,
                'type' => OptionalSelect::class,
                'options' => [
                    'label' => $property->term(),
                    'value_options' => $fieldOptions,
                    'empty_option' => 'None', // @translate
                ],
                'attributes' => [
                    'required' => false,
                    'class' => 'chosen-select',
                ],
            ]);
        }

        $this->add($propertiesFieldset);
    }

    protected function getAvailableFields()
    {
        $searchPage = $this->getOption('search_page');
        $searchIndex = $searchPage->index();
        $searchAdapter = $searchIndex->adapter();
        return $searchAdapter->getAvailableFields($searchIndex);
    }

    protected function getFieldsOptions()
    {
        $options = [];
        foreach ($this->getAvailableFields() as $name => $field) {
            $options[$name] = isset($field['label'])
                ? sprintf('%s (%s)', $field['label'], $name)
                : $name;
        }
        return $options;
    }

    public function setApiManager(ApiManager $api)
    {
        $this->api = $api;
    }

    public function getApiManager()
    {
        return $this->api;
    }
}
