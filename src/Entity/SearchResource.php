<?php

namespace Search\Entity;

use DateTime;
use Omeka\Entity\AbstractEntity;
use Omeka\Entity\Resource;

/**
 * @Entity
 * @Table(
 *     indexes={
 *         @Index(columns={"locked_by_pid"}),
 *         @Index(columns={"indexed"}),
 *         @Index(columns={"touched"})
 *     },
 *     uniqueConstraints={
 *         @UniqueConstraint(columns={"index_id", "resource_id"})
 *     }
 * )
 */
class SearchResource extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(
     *     targetEntity="SearchIndex",
     * )
     * @JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected $index;

    /**
     * @ManyToOne(
     *     targetEntity="Omeka\Entity\Resource",
     * )
     * @JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected $resource;

    /**
     * @Column(type="integer", nullable=true)
     */
    protected $lockedByPid;

    /**
     * @Column(type="datetime", nullable=true)
     */
    protected $indexed;

    /**
     * @Column(type="datetime", nullable=true)
     */
    protected $touched;

    public function getId()
    {
        return $this->id;
    }

    public function getIndex(): SearchIndex
    {
        return $this->index;
    }

    public function setIndex(SearchIndex $index)
    {
        $this->index = $index;
    }

    public function getResource(): Resource
    {
        return $this->resource;
    }

    public function setResource(Resource $resource)
    {
        $this->resource = $resource;
    }

    public function getLockedByPid(): int
    {
        return $this->lockedByPid;
    }

    public function setLockedByPid(int $pid)
    {
        $this->lockedByPid = $pid;
    }

    public function getIndexed()
    {
        return $this->indexed;
    }

    public function setIndexed(DateTime $indexed)
    {
        $this->indexed = $indexed;
    }

    public function getTouched()
    {
        return $this->touched;
    }

    public function setTouched(DateTime $touched)
    {
        $this->touched = $touched;
    }
}
