<?php

declare(strict_types=1);

namespace App\Entity;

use DateInterval;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(
 *   name="supplier_delivery_schedule",
 *   uniqueConstraints={
 *   },
 * )
 */
class SupplierDeliverySchedule
{
    use Traits\Timestampable;

    private const WEEK_DAYS = [
        1 => 'Понедельник',
        2 => 'Вторник',
        3 => 'Среда',
        4 => 'Четверг',
        5 => 'Пятница',
        6 => 'Суббота',
        7 => 'Воскресенье',
    ];

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
     *   targetEntity="Supplier",
     *   inversedBy="deliverySchedule",
     * )
     *
     * @var Supplier|null
     */
    protected $supplier;

    /**
     * @ORM\ManyToOne(
     *   targetEntity="Warehouse",
     *   inversedBy="deliverySchedule",
     * )
     *
     * @var Warehouse|null
     */
    protected $warehouse;

    /**
     * @ORM\Column(
     *  type="integer",
     *  options={
     *    "unsigned": true,
     *  }
     * )
     *
     * @var int|null
     */
    protected $day_number;

    /**
     * @ORM\Column(
     *   type="time",
     *   nullable=false,
     * )
     *
     * @var DateTime|null
     */
    protected $order_time;

    /**
     * @ORM\Column(
     *   type="dateinterval",
     *   nullable=false,
     * )
     *
     * @var DateInterval|null
     */
    protected $delivery_time_amount;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Supplier|null
     */
    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    /**
     * @param Supplier $supplier
     * @return self
     */
    public function setSupplier(Supplier $supplier): self
    {
        $this->supplier = $supplier;

        return $this;
    }

    /**
     * @return Warehouse|null
     */
    public function getWarehouse(): ?Warehouse
    {
        return $this->warehouse;
    }

    /**
     * @param Warehouse $warehouse
     * @return self
     */
    public function setWarehouse(Warehouse $warehouse): self
    {
        $this->warehouse = $warehouse;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getDayNumber(): ?int
    {
        return $this->day_number;
    }

    /**
     * @param int $dayNumber
     * @return self
     */
    public function setDayNumber(int $dayNumber): self
    {
        $this->day_number = $dayNumber;

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

        return $this;
    }

    /**
     * @return DateInterval|null
     */
    public function getDeliveryTimeAmount(): ?DateInterval
    {
        return $this->delivery_time_amount;
    }

    /**
     * @param DateInterval $timeAmount
     * @return self
     */
    public function setDeliveryTimeAmount(DateInterval $timeAmount): self
    {
        $this->delivery_time_amount = $timeAmount;

        return $this;
    }

    public function getDay(): string
    {
        return self::WEEK_DAYS[$this->day_number];
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return sprintf('%s %s, %s', self::WEEK_DAYS[$this->day_number], $this->order_time->format('H:i'), $this->warehouse->getName());
    }
}
