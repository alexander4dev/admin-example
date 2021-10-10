<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="delivery_sector")
 */
class Sector
{
    use Traits\Timestampable;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(
     *   type="integer",
     *   options={
     *     "unsigned": true,
     *   },
     * )
     *
     * @var int|null
     */
    protected $id;

    /**
     * @ORM\Column(
     *   type="string",
     *   nullable=false,
     * )
     *
     * @var string|null
     */
    protected $name;

    /**
     * @ORM\ManyToOne(
     *   targetEntity="Branch",
     *   inversedBy="sectors",
     * )
     *
     * @var Branch|null
     */
    protected $branch;

    /**
     * @ORM\OneToMany(
     *   targetEntity="DeliverySectorRouteSheet",
     *   mappedBy="sector",
     *   cascade={"persist", "remove"}
     * )
     */
    protected $deliveryArrivals;

    public function __construct()
    {
        $this->deliveryArrivals = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Branch|null
     */
    public function getBranch(): ?Branch
    {
        return $this->branch;
    }

    /**
     * @param Branch $branch
     * @return self
     */
    public function setBranch(Branch $branch): self
    {
        $branch->addSector($this);
        $this->branch = $branch;

        return $this;
    }

    /**
     * @return PersistentCollection
     */
    public function getDeliveryArrivals(): PersistentCollection
    {
        return $this->deliveryArrivals;
    }

    /**
     * @param DeliverySectorRouteSheet $deliveryArrival
     * @return self
     */
    public function addDeliveryArrival(DeliverySectorRouteSheet $deliveryArrival): self
    {
        $this->deliveryArrivals->add($deliveryArrival);

        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return sprintf('%s (#%d)', $this->name, $this->id);
    }
}
