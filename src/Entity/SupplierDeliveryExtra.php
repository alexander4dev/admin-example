<?php

declare(strict_types=1);

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(
 *   name="delivery_extra",
 *   uniqueConstraints={
 *   },
 * )
 */
class SupplierDeliveryExtra
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
     * @ORM\ManyToOne(
     *   targetEntity="AbstractSupplier",
     *   inversedBy="deliveryExtraOutgoing",
     * )
     *
     * @var AbstractSupplier|null
     */
    protected $supplier_from;

    /**
     * @ORM\ManyToOne(
     *   targetEntity="AbstractSupplier",
     *   inversedBy="deliveryExtraIncoming",
     * )
     *
     * @var AbstractSupplier|null
     */
    protected $supplier_to;

    /**
     * @ORM\Column(
     *   type="date",
     *   nullable=false,
     * )
     */
    protected $order_date;

    /**
     * @ORM\Column(
     *   type="time",
     *   nullable=false,
     * )
     */
    protected $order_time;

    /**
     * @ORM\Column(
     *   type="boolean",
     *   nullable=false,
     * )
     */
    protected $is_supply;

    /**
     * @ORM\Column(
     *   type="datetime",
     *   nullable=true,
     * )
     */
    protected $delivery_date;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return AbstractSupplier|null
     */
    public function getSupplierFrom(): ?AbstractSupplier
    {
        return $this->supplier_from;
    }

    /**
     * @param AbstractSupplier $supplierFrom
     * @return self
     */
    public function setSupplierFrom(AbstractSupplier $supplierFrom): self
    {
        $this->supplier_from = $supplierFrom;

        return $this;
    }

    /**
     * @return AbstractSupplier|null
     */
    public function getSupplierTo(): ?AbstractSupplier
    {
        return $this->supplier_to;
    }

    /**
     * @param AbstractSupplier $supplierTo
     * @return self
     */
    public function setSupplierTo(AbstractSupplier $supplierTo): self
    {
        $this->supplier_to = $supplierTo;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getOrderDate(): ?DateTime
    {
        return $this->order_date;
    }

    /**
     * @param DateTime $orderDate
     * @return self
     */
    public function setOrderDate(DateTime $orderDate): self
    {
        $this->order_date = $orderDate;
//        $this->order_date->setTime(0, 0);

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getOrderTime(): ?DateTime
    {
        return $this->order_time;
    }

    /**
     * @param DateTime $orderTime
     * @return self
     */
    public function setOrderTime(DateTime $orderTime): self
    {
        $this->order_time = $orderTime;
//        $this->order_time->setTime(0, 0);

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsSupply(): ?bool
    {
        return $this->is_supply;
    }

    /**
     * @param bool $isSupply
     * @return self
     */
    public function setIsSupply(bool $isSupply): self
    {
        $this->is_supply = $isSupply;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getDeliveryDate(): ?DateTime
    {
        return $this->delivery_date;
    }

    /**
     * @param DateTime $deliveryDate
     * @return self
     */
    public function setDeliveryDate(?DateTime $deliveryDate): self
    {
        $this->delivery_date = $deliveryDate;
//        $this->delivery_date->setTime(0, 0);

        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $departureName = sprintf('%s (%s %s)', $this->getSupplierFrom()->getName(), $this->getOrderDate()->format('d.m.Y'), $this->getOrderTime()->format('H:i'));
        $arrivalName = sprintf('%s (%s)', $this->getSupplierto()->getName(), $this->is_supply ? $this->getDeliveryDate()->format('d.m.Y H:i') : 'Поставка отменена');

        return sprintf('%s - %s', $departureName, $arrivalName);
    }
}
