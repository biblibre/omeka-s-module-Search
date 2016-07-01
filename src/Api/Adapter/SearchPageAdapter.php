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

namespace Search\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class SearchPageAdapter extends AbstractEntityAdapter
{
    /**
     * {@inheritDoc}
     */
    protected $sortFields = [
        'id'        => 'id',
        'name'      => 'name',
        'created'   => 'created',
        'modified'  => 'modified',
    ];

    /**
     * {@inheritDoc}
     */
    public function getResourceName()
    {
        return 'search_pages';
    }

    /**
     * {@inheritDoc}
     */
    public function getRepresentationClass()
    {
        return 'Search\Api\Representation\SearchPageRepresentation';
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass()
    {
        return 'Search\Entity\SearchPage';
    }

    /**
     * {@inheritDoc}
     */
    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        if ($this->shouldHydrate($request, 'o:name')) {
            $entity->setName($request->getValue('o:name'));
        }
        if ($this->shouldHydrate($request, 'o:path')) {
            $entity->setPath($request->getValue('o:path'));
        }
        if ($this->shouldHydrate($request, 'o:index_id')) {
            $indexId = $request->getValue('o:index_id');
            $entity->setIndex($this->getAdapter('search_indexes')->findEntity($indexId));
        }
        if ($this->shouldHydrate($request, 'o:form')) {
            $entity->setFormAdapter($request->getValue('o:form'));
        }
        if ($this->shouldHydrate($request, 'o:settings')) {
            $entity->setSettings($request->getValue('o:settings'));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        if (false == $entity->getName()) {
            $errorStore->addError('o:name', 'The name cannot be empty.');
        }

        $path = $entity->getPath();
        if (!$this->isUnique($entity, ['path' => $path])) {
            $errorStore->addError('o:path', sprintf('The path "%s" is already taken.', $path));
        }
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['name'])) {
            $qb->andWhere($qb->expr()->eq(
                $this->getEntityClass() . ".name",
                $this->createNamedParameter($qb, $query['name']))
            );
        }
        if (isset($query['path'])) {
            $qb->andWhere($qb->expr()->eq(
                $this->getEntityClass() . ".path",
                $this->createNamedParameter($qb, $query['path']))
            );
        }
        if (isset($query['form'])) {
            $qb->andWhere($qb->expr()->eq(
                $this->getEntityClass() . ".form",
                $this->createNamedParameter($qb, $query['form']))
            );
        }
    }
}
