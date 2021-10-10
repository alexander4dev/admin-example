<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(
 *   name="discriminator",
 *   type="string",
 * )
 * @ORM\Table(
 *   name="abstract_supplier",
 * )
 */
abstract class AbstractSupplier extends AbstractWorkingPlace
{
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
     * @ORM\OneToMany(
     *   targetEntity="SupplierDeliveryExtra",
     *   mappedBy="supplier_from",
     *   cascade={"persist", "remove"}
     * )
     */
    protected $deliveryExtraOutgoing;

    /**
     * @ORM\OneToMany(
     *   targetEntity="SupplierDeliveryExtra",
     *   mappedBy="supplier_to",
     *   cascade={"persist", "remove"}
     * )
     */
    protected $deliveryExtraIncoming;

    public function __construct()
    {
        $this->deliveryExtraOutgoing = new ArrayCollection();
        $this->deliveryExtraIncoming = new ArrayCollection();

        parent::__construct();
    }

    /**
     * @return PersistentCollection
     */
    public function getDeliveryExtraOutgoing(): PersistentCollection
    {
        return $this->deliveryExtraOutgoing;
    }

    /**
     * @param SupplierDeliveryExtra $deliveryExtraOutgoing
     * @return self
     */
    public function addDeliveryExtraOutgoing(SupplierDeliveryExtra $deliveryExtraOutgoing): self
    {
        $this->deliveryExtraOutgoing->add($deliveryExtraOutgoing);

        return $this;
    }

    /**
     * @return PersistentCollection
     */
    public function getDeliveryExtraIncoming(): PersistentCollection
    {
        return $this->deliveryExtraIncoming;
    }

    /**
     * @param SupplierDeliveryExtra $deliveryExtraIncoming
     * @return self
     */
    public function addDeliveryExtraIncoming(SupplierDeliveryExtra $deliveryExtraIncoming): self
    {
        $this->deliveryExtraIncoming->add($deliveryExtraIncoming);

        return $this;
    }
}
