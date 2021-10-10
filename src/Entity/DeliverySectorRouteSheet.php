<?php

declare(strict_types=1);

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(
 *   name="delivery_sector_route_sheet",
 *   uniqueConstraints={
 *   },
 * )
 */
class DeliverySectorRouteSheet
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
     *   inversedBy="deliverySectorDepartures",
     * )
     *
     * @var Warehouse|null
     */
    protected $warehouse;

    /**
     * @ORM\ManyToOne(
     *   targetEntity="Sector",
     *   inversedBy="deliveryArrivals",
     * )
     * 
     * @var Warehouse|null
     */
    protected $sector;

    /**
     * @ORM\Column(
     *   type="time",
     *   nullable=false,
     * )
     *
     * @var DateTime|null
     */
    protected $time_departure;

    /**
     * @ORM\Column(
     *   type="time",
     *   nullable=false,
     * )
     *
     * @var DateTime|null
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
        $warehouse->addDeliverySectorDeparture($this);
        $this->warehouse = $warehouse;

        return $this;
    }

    /**
     * @return Sector|null
     */
    public function getSector(): ?Sector
    {
        return $this->sector;
    }

    /**
     * @param Sector $sector
     * @return self
     */
    public function setSector(Sector $sector): self
    {
        $this->sector = $sector;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getTimeDeparture(): ?DateTime
    {
        return $this->time_departure;
    }

    /**
     * @param DateTime $timeDeparture
     * @return self
     */
    public function setTimeDeparture(DateTime $timeDeparture): self
    {
        $this->time_departure = $timeDeparture;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getTimeArrival(): ?DateTime
    {
        return $this->time_arrival;
    }

    /**
     * @param DateTime $timeArrival
     * @return self
     */
    public function setTimeArrival(DateTime $timeArrival): self
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
            $this->warehouse->getName(),
            $this->time_departure->format('H:i'),
            $this->sector->getName(),
            $this->time_arrival->format('H:i')
        );
    }
}
