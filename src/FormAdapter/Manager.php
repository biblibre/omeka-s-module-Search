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

namespace Search\FormAdapter;

class Manager
{
    protected $config;
    protected $adapters;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function get($name)
    {
        if (isset($this->adapters[$name])) {
            return $this->adapters[$name];
        }

        if (!isset($this->config[$name])) {
            return null;
        }

        $class = $this->config[$name];
        if (!class_exists($class)) {
            return null;
        }

        if (!in_array('Search\FormAdapter\FormAdapterInterface', class_implements($class))) {
            return null;
        }

        $this->adapters[$name] = new $class;
        return $this->adapters[$name];
    }

    public function getAll()
    {
        $adapters = [];
        foreach ($this->config as $name => $class) {
            $adapter = $this->get($name);
            if ($adapter !== null) {
                $adapters[$name] = $adapter;
            }
        }
        return $adapters;
    }
}
