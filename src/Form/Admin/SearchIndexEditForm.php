<?php

/*
 * Copyright BibLibre, 2016
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

use Laminas\Form\Fieldset;
use Search\Adapter\AdapterInterface;

class SearchIndexEditForm extends SearchIndexAddForm
{
    protected $apiManager;
    protected AdapterInterface $adapter;

    public function init()
    {
        parent::init();

        $this->get('o:adapter')->setAttributes(['disabled' => true, 'required' => null]);

        $settingsFieldset = new Fieldset('o:settings');
        $this->add($settingsFieldset);

        $settingsFieldset->add([
            'name' => 'resources',
            'type' => 'MultiCheckbox',
            'options' => [
                'label' => 'Resources indexed', // @translate
                'value_options' => $this->getResourcesOptions(),
            ],
            'attributes' => [
                'value' => ['items'],
            ],
        ]);

        $adapterFieldset = $this->getAdapter()->getConfigFieldset();
        $adapterFieldset->setName('adapter');
        $adapterFieldset->setLabel('Adapter settings');
        $settingsFieldset->add($adapterFieldset);

        $this->getInputFilter()->add(['name' => 'o:adapter', 'required' => false]);
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
        return $this->getAdapter()->getHandledResources();
    }

    protected function getAdapter(): AdapterInterface
    {
        if (!isset($this->adapter)) {
            $api = $this->getApiManager();

            $searchIndexId = $this->getOption('search_index_id');
            $response = $api->read('search_indexes', $searchIndexId);
            $searchIndex = $response->getContent();
            $indexer = $searchIndex->indexer();

            $this->adapter = $searchIndex->adapter();
        }

        return $this->adapter;
    }
}
