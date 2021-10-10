<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(
 *   name="delivery_route_sheet",
 *   uniqueConstraints={
 *   },
 * )
 */
class DeliveryRouteSheet
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
     * @ORM\ManyToOne(
     *   targetEntity="Warehouse",
     *   inversedBy="deliveryDepartures",
     * )
     *
     * @var Warehouse|null
     */
    protected $warehouse_from;

    /**
     * @ORM\ManyToOne(
     *   targetEntity="Warehouse",
     *   inversedBy="deliveryArrivals",
     * )
     * 
     * @var Warehouse|null
     */
    protected $warehouse_to;

    /**
     * @ORM\Column(
     *   type="time",
     *   nullable=false,
     * )
     */
    protected $time_departure;

    /**
     * @ORM\Column(
     *   type="time",
     *   nullable=false,
     * )
     */
    protected $time_arrival;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Warehouse|null
     */
    public function getWarehouseFrom(): ?Warehouse
    {
        return $this->warehouse_from;
    }

    /**
     * @param Warehouse $warehouseFrom
     * @return self
     */
    public function setWarehouseFrom(Warehouse $warehouseFrom): self
    {
        $this->warehouse_from = $warehouseFrom;

        return $this;
    }

    /**
     * @return Warehouse|null
     */
    public function getWarehouseTo(): ?Warehouse
    {
        return $this->warehouse_to;
    }

    /**
     * @param Warehouse $warehouseTo
     * @return self
     */
    public function setWarehouseTo(Warehouse $warehouseTo): self
    {
        $this->warehouse_to = $warehouseTo;

        return $this;
    }

    public function getTimeDeparture()
    {
        return $this->time_departure;
    }

    public function setTimeDeparture($timeDeparture): self
    {
        $this->time_departure = $timeDeparture;

        return $this;
    }

    public function getTimeArrival()
    {
        return $this->time_arrival;
    }

    public function setTimeArrival($timeArrival): self
    {
        $this->time_arrival = $timeArrival;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return sprintf(
            '%s (%s) - %s (%s)',
            $this->warehouse_from->getName(),
            $this->time_departure->format('H:i'),
            $this->warehouse_to->getName(),
            $this->time_arrival->format('H:i')
        );
    }
}
