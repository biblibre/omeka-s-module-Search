<?php

/*
 * Copyright BibLibre, 2016
 * Copyright Daniel Berthereau, 2017-2018
 *
 * This software is governed by the CeCILL license under French law and abiding
 * by the rules of distribution of free software.  You can use, modify and/ or
 * redistribute the software under the terms of the CeCILL license as circulated
 * by CEA, CNRS and INRIA at the following URL "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and rights to copy, modify
 * and redistribute granted by the license, users are provided only with a
 * limited warranty and the software's author, the holder of the economic
 * rights, and the successive licensors have only limited liability.
 *
 * In this respect, the user's attention is drawn to the risks associated with
 * loading, using, modifying and/or developing or reproducing the software by
 * the user in light of its specific status of free software, that may mean that
 * it is complicated to manipulate, and that also therefore means that it is
 * reserved for developers and experienced professionals having in-depth
 * computer knowledge. Users are therefore encouraged to load and test the
 * software's suitability as regards their requirements in conditions enabling
 * the security of their systems and/or data to be ensured and, more generally,
 * to use and operate it in the same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
 */

namespace Search\Form\Admin;

use Zend\Form\Element\MultiCheckbox;
use Zend\Form\Form;
use Zend\I18n\Translator\TranslatorAwareInterface;
use Zend\I18n\Translator\TranslatorAwareTrait;

class SearchIndexConfigureForm extends Form implements TranslatorAwareInterface
{
    use TranslatorAwareTrait;

    protected $apiManager;

    public function init()
    {
        $translator = $this->getTranslator();

        $this->add([
            'name' => 'resources',
            'type' => MultiCheckbox::class,
            'options' => [
                'label' => 'Resources indexed', // @translate
                'value_options' => $this->getResourcesOptions(),
            ],
            'attributes' => [
                'value' => ['items'],
            ],
        ]);
    }

    public function setApiManager($apiManager)
    {
        $this->apiManager = $apiManager;
    }

    public function getApiManager()
    {
        return $this->apiManager;
    }

    protected function getResourcesOptions()
    {
        $api = $this->getApiManager();
        $translator = $this->getTranslator();

        $searchIndexId = $this->getOption('search_index_id');
        $response = $api->read('search_indexes', $searchIndexId);
        $searchIndex = $response->getContent();
        $indexer = $searchIndex->indexer();

        $options = [
            'items' => $translator->translate('Items'),
            'item_sets' => $translator->translate('Item sets'),
        ];

        return $options;
    }
}
