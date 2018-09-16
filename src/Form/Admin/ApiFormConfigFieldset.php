<?php
namespace Search\Form\Admin;

use Omeka\Api\Manager as ApiManager;
use Search\Form\Element\OptionalSelect;
use Zend\Form\Element;
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

        $generalFieldset = new Fieldset('options');
        $generalFieldset->setLabel('Main options'); // @translate
        $generalFieldset->setAttribute('id', 'options');

        $generalFieldset->add([
            'name' => 'max_results',
            'type' => Element\Number::class,
            'options' => [
                'label' => 'Maximum number of results by response', // @translate
                'info' => 'It is recommended to keep the value low (under 100 or 1000) to avoid overload of the server, or to use a paginator.', // @translate
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);
        $this->add($generalFieldset);

        $metadataFieldset = new Fieldset('metadata');
        $metadataFieldset->setLabel('Mapping metadata to search fields'); // @translate
        $metadataFieldset->setAttribute('id', 'metadata');

        $metadataFieldset->add([
            'name' => 'id',
            'type' => OptionalSelect::class,
            'options' => [
                'label' => 'Internal identifier', // @translate
                'value_options' => $fieldOptions,
                'empty_option' => 'None', // @translate
            ],
            'attributes' => [
                'required' => false,
                'class' => 'chosen-select',
            ],
        ]);

        $metadataFieldset->add([
            'name' => 'is_public',
            'type' => OptionalSelect::class,
            'options' => [
                'label' => 'Is Public', // @translate
                'value_options' => $fieldOptions,
                'empty_option' => 'None', // @translate
            ],
            'attributes' => [
                'required' => false,
                'class' => 'chosen-select',
            ],
        ]);

        $metadataFieldset->add([
            'name' => 'owner_id',
            'type' => OptionalSelect::class,
            'options' => [
                'label' => 'Owner id', // @translate
                'value_options' => $fieldOptions,
                'empty_option' => 'None', // @translate
            ],
            'attributes' => [
                'required' => false,
                'class' => 'chosen-select',
            ],
        ]);

        $metadataFieldset->add([
            'name' => 'created',
            'type' => OptionalSelect::class,
            'options' => [
                'label' => 'Created', // @translate
                'value_options' => $fieldOptions,
                'empty_option' => 'None', // @translate
            ],
            'attributes' => [
                'required' => false,
                'class' => 'chosen-select',
            ],
        ]);

        $metadataFieldset->add([
            'name' => 'modified',
            'type' => OptionalSelect::class,
            'options' => [
                'label' => 'Modified', // @translate
                'value_options' => $fieldOptions,
                'empty_option' => 'None', // @translate
            ],
            'attributes' => [
                'required' => false,
                'class' => 'chosen-select',
            ],
        ]);

        $metadataFieldset->add([
            'name' => 'resource_class_label',
            'type' => OptionalSelect::class,
            'options' => [
                'label' => 'Resource class label', // @translate
                'value_options' => $fieldOptions,
                'empty_option' => 'None', // @translate
            ],
            'attributes' => [
                'required' => false,
                'class' => 'chosen-select',
            ],
        ]);

        $metadataFieldset->add([
            'name' => 'resource_class_id',
            'type' => OptionalSelect::class,
            'options' => [
                'label' => 'Resource class id', // @translate
                'value_options' => $fieldOptions,
                'empty_option' => 'None', // @translate
            ],
            'attributes' => [
                'required' => false,
                'class' => 'chosen-select',
            ],
        ]);

        $metadataFieldset->add([
            'name' => 'resource_template_id',
            'type' => OptionalSelect::class,
            'options' => [
                'label' => 'Resource template id', // @translate
                'value_options' => $fieldOptions,
                'empty_option' => 'None', // @translate
            ],
            'attributes' => [
                'required' => false,
                'class' => 'chosen-select',
            ],
        ]);

        $metadataFieldset->add([
            'name' => 'item_set_id',
            'type' => OptionalSelect::class,
            'options' => [
                'label' => 'Item set id', // @translate
                'value_options' => $fieldOptions,
                'empty_option' => 'None', // @translate
            ],
            'attributes' => [
                'required' => false,
                'class' => 'chosen-select',
            ],
        ]);

        $metadataFieldset->add([
            'name' => 'site_id',
            'type' => OptionalSelect::class,
            'options' => [
                'label' => 'Site id', // @translate
                'value_options' => $fieldOptions,
                'empty_option' => 'None', // @translate
            ],
            'attributes' => [
                'required' => false,
                'class' => 'chosen-select',
            ],
        ]);

        $metadataFieldset->add([
            'name' => 'is_open',
            'type' => OptionalSelect::class,
            'options' => [
                'label' => 'Is open', // @translate
                'value_options' => $fieldOptions,
                'empty_option' => 'None', // @translate
            ],
            'attributes' => [
                'required' => false,
                'class' => 'chosen-select',
            ],
        ]);

        $this->add($metadataFieldset);

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

        // Quick hack to get the available sort fields inside the form settings
        // The sort fields may be different from the indexed fields in some
        // search engine, so they should be checked when the api is used, since
        // there is no user form validation.
        $sortFieldset = new Fieldset('sort_fields');
        $sortFields = $this->getAvailableSortFields();
        foreach (array_keys($sortFields) as $key => $sortField) {
            $sortFieldset->add([
                'name' => $key,
                'type' => Element\Hidden::class,
                'attributes' => [
                    'value' => $sortField,
                ],
            ]);
        }
        $this->add($sortFieldset);
    }

    protected function getAvailableFields()
    {
        $searchPage = $this->getOption('search_page');
        $searchIndex = $searchPage->index();
        $searchAdapter = $searchIndex->adapter();
        return $searchAdapter->getAvailableFields($searchIndex);
    }

    protected function getAvailableSortFields()
    {
        $searchPage = $this->getOption('search_page');
        $searchIndex = $searchPage->index();
        $searchAdapter = $searchIndex->adapter();
        return $searchAdapter->getAvailableSortFields($searchIndex);
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
