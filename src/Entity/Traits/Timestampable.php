<?php

declare(strict_types=1);

namespace App\Entity\Traits;

use Datetime;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;

trait Timestampable
{
    /**
     * @ORM\Column(
     *   type="datetime",
     *   nullable=false,
     *   options={
     *     "default": "CURRENT_TIMESTAMP"
     *   }
     * )
     *
     * @var Datetime|null
     */
    protected $created_at;

    /**
     * @ORM\Column(
     *   type="datetime",
     *   nullable=false,
     *   options={
     *     "default": "CURRENT_TIMESTAMP",
     *   }
     * )
     *
     * @var Datetime|null
     */
    protected $updated_at;

    /**
     * @return Datetime|null
     */
    public function getCreatedAt(): ?Datetime
    {
        return $this->created_at;
    }

    /**
     * @param Datetime $createdAt
     * @return self
     */
    public function setCreatedAt(Datetime $createdAt): self
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * @return Datetime|null
     */
    public function getUpdatedAt(): ?Datetime
    {
        return $this->updated_at;
    }

    /**
     * @param Datetime $updatedAt
     * @return self
     */
    public function setUpdatedAt(Datetime $updatedAt): self
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * @ORM\PrePersist
     *
     * @param LifecycleEventArgs $eventArgs
     * @return void
     */
    public function prePersist(LifecycleEventArgs $eventArgs): void
    {
        $now = new Datetime();

        foreach (['created_at', 'updated_at'] as $timestampable) {
            if (null === $this->$timestampable) {
                $this->$timestampable = $now;
            }
        }
    }

    /**
     * @ORM\PreUpdate
     *
     * @param PreUpdateEventArgs $eventArgs
     * @return void
     */
    public function preUpdate(PreUpdateEventArgs $eventArgs): void
    {
        if (!$eventArgs->hasChangedField('updated_at')) {
            $this->updated_at = new Datetime();
        }
    }
}
