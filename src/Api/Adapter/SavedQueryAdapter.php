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

class SavedQueryAdapter extends AbstractEntityAdapter
{
    /**
     * {@inheritDoc}
     */
    protected $sortFields = [
        'id' => 'id',
    ];

    /**
     * {@inheritDoc}
     */
    public function getRepresentationClass()
    {
        return 'Search\Api\Representation\SavedQueryRepresentation';
    }

    /**
     * {@inheritDoc}
     */
    public function getResourceName()
    {
        return 'saved_queries';
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass()
    {
        return 'Search\Entity\SavedQuery';
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['user_id']) && is_numeric($query['user_id'])) {
            $userAlias = $this->createAlias();
            $qb->innerJoin(
                'omeka_root.user',
                $userAlias
            );

            $qb->andWhere(
                $qb->expr()->eq(
                    "$userAlias.id",
                    $this->createNamedParameter($qb, $query['user_id'])
                )
            );
        }
    }

    /**
     * @param \Search\Entity\SavedQuery $entity
     */
    public function hydrate(
        Request $request,
        EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        $user = $this->getServiceLocator()->get('Omeka\AuthenticationService')->getIdentity();
        if ($user) {
            $entity->setUser($user);
        }

        if ($this->shouldHydrate($request, 'o:site_id')) {
            /** @var \Omeka\Api\Adapter\SiteAdapter */
            $siteAdapter = $this->getAdapter('sites');
            $site = $siteAdapter->findEntity($request->getValue('o:site_id'));
            $entity->setSite($site);
        }

        if ($this->shouldHydrate($request, 'o:search_page_id')) {
            /** @var \Omeka\Api\Adapter\SiteAdapter */
            $searchPageAdapter = $this->getAdapter('search_pages');
            $searchPage = $searchPageAdapter->findEntity($request->getValue('o:search_page_id'));
            $entity->setSearchPage($searchPage);
        }

        if ($this->shouldHydrate($request, 'o:query_string')) {
            $entity->setQueryString($request->getValue('o:query_string'));
        }

        if ($this->shouldHydrate($request, 'o:query_title')) {
            $entity->setQueryTitle($request->getValue('o:query_title'));
        }

        if ($this->shouldHydrate($request, 'o:query_description')) {
            $entity->setQueryDescription($request->getValue('o:query_description'));
        }
    }
}
