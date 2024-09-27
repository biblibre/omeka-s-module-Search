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

namespace Search\Entity;

use DateTime;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Omeka\Entity\AbstractEntity;

/**
 * @Entity
 */
class SavedQuery extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Omeka\Entity\User")
     * @JoinColumn(onDelete="CASCADE")
     */
    protected $user;

    /**
     * @ManyToOne(targetEntity="Omeka\Entity\Site")
     * @JoinColumn(onDelete="CASCADE")
     */
    protected $site;

    /**
     * @ManyToOne(targetEntity="SearchPage")
     * @JoinColumn(onDelete="CASCADE")
     */
    protected $search_page;

    /**
     * @Column(type="text", nullable=false)
     */
    protected $query_string;

    /**
     * @Column(type="string", length=255)
     */
    protected $query_title;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $query_description;

    public function getId()
    {
        return $this->id;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function getSite()
    {
        return $this->site;
    }

    public function setSite($site)
    {
        $this->site = $site;
    }

    public function getSearchPage()
    {
        return $this->search_page;
    }

    public function setSearchPage($search_page)
    {
        $this->search_page = $search_page;
    }

    public function setQueryString($query_string)
    {
        $this->query_string = $query_string;
    }

    public function getQueryString()
    {
        return $this->query_string;
    }

    public function setQueryTitle($query_title)
    {
        $this->query_title = $query_title;
    }

    public function getQueryTitle()
    {
        return $this->query_title;
    }

    public function setQueryDescription($query_description)
    {
        $this->query_description = $query_description;
    }

    public function getQueryDescription()
    {
        return $this->query_description;
    }
}
