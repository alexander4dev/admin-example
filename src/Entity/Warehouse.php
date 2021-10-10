<?php

declare(strict_types=1);

namespace App\Entity;

use DateInterval;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="warehouse")
 */
class Warehouse extends AbstractSupplier
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
     * @ORM\OneToOne(
     *   targetEntity="Branch",
     *   inversedBy="warehouse",
     * )
     *
     * @var Branch|null
     */
    protected $branch;

    /**
     * @ORM\OneToMany(
     *   targetEntity="DeliveryRouteSheet",
     *   mappedBy="warehouse_to",
     *   cascade={"persist", "remove"}
     * )
     */
    protected $deliveryArrivals;

    /**
     * @ORM\OneToMany(
     *   targetEntity="DeliveryRouteSheet",
     *   mappedBy="warehouse_from",
     *   cascade={"persist", "remove"}
     * )
     */
    protected $deliveryDepartures;

    /**
     * @ORM\OneToMany(
     *   targetEntity="DeliverySectorRouteSheet",
     *   mappedBy="warehouse",
     *   cascade={"persist", "remove"}
     * )
     */
    protected $deliverySectorDepartures;

    /**
     * @ORM\OneToMany(
     *   targetEntity="SupplierDeliverySchedule",
     *   mappedBy="warehouse",
     *   cascade={"persist", "remove"}
     * )
     */
    protected $deliverySchedule;

    public function __construct()
    {
        $this->deliveryArrivals = new ArrayCollection();
        $this->deliveryDepartures = new ArrayCollection();
        $this->deliverySectorDepartures = new ArrayCollection();
        $this->deliverySchedule = new ArrayCollection();

        parent::__construct();
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
        $branch->setWarehouse($this);
        $this->branch = $branch;

        return $this;
    }

    public function getDeliveryArrivals()
    {
        return $this->deliveryArrivals;
    }

    /**
     * @param DeliveryRouteSheet $deliveryArrival
     * @return self
     */
    public function addDeliveryArrival(DeliveryRouteSheet $deliveryArrival): self
    {
        $this->deliveryArrivals->add($deliveryArrival);

        return $this;
    }

    public function getDeliveryDepartures()
    {
        return $this->deliveryDepartures;
    }

    /**
     * @param DeliveryRouteSheet $deliveryDeparture
     * @return self
     */
    public function addDeliveryDeparture(DeliveryRouteSheet $deliveryDeparture): self
    {
        $this->deliveryDepartures->add($deliveryDeparture);

        return $this;
    }

    public function getDeliverySchedule()
    {
        return $this->deliverySchedule;
    }

    /**
     * @param SupplierDeliverySchedule $deliverySchedule
     * @return self
     */
    public function addDeliverySchedule(SupplierDeliverySchedule $deliverySchedule): self
    {
        $this->deliverySchedule->add($deliverySchedule);

        return $this;
    }

    /**
     * @return PersistentCollection
     */
    public function getDeliverySectorDepartures(): PersistentCollection
    {
        return $this->deliverySectorDepartures;
    }

    /**
     * @param DeliverySectorRouteSheet $deliverySectorDeparture
     * @return self
     */
    public function addDeliverySectorDeparture(DeliverySectorRouteSheet $deliverySectorDeparture): self
    {
        $this->deliverySectorDepartures->add($deliverySectorDeparture);

        return $this;
    }

    /**
     * @param int $warehouseId
     * @param DateTime $date
     * @return DateTime
     */
    public function getClosestDepartureDate(int $warehouseId, DateTime $date = null): DateTime
    {
        if (null === $date) {
            $currentDate = new DateTime();
        } else {
            $currentDate = clone $date;
        }

        $oneDayInterval = new DateInterval('P1D');
        $result = false;
        $cycleCount = 0;
        $closestWorkingDay = $this->getClosestWorkingDay($currentDate)['date'];
        $closestWorkingDay->setTime((int)$currentDate->format('G'), (int)$currentDate->format('i'));

        do {
            if (100500 === ++$cycleCount) {
                die(var_dump(__METHOD__));
            }

            $routes = [];
            $routesArrivals = [];

            foreach ($this->deliveryDepartures as $deliveryRoute) {
                /* @var $deliveryRoute DeliveryRouteSheet */
                if ($warehouseId !== $deliveryRoute->getWarehouseTo()->getId()) {
                    continue;
                }

                /* @var $timeDeparture DateTime */
                $timeDeparture = clone $deliveryRoute->getTimeDeparture();
                $timeDeparture->setDate((int)$closestWorkingDay->format('Y'), (int)$closestWorkingDay->format('n'), (int)$closestWorkingDay->format('j'));

                if ($closestWorkingDay < $timeDeparture) {
                    $routes[] = $timeDeparture;
                    $routesArrivals[$timeDeparture->getTimestamp()] = $deliveryRoute->getTimeDeparture();
                }
            }

            if (!$routes) {
                $closestWorkingDay->add($oneDayInterval);
                $closestWorkingDay = $this->getClosestWorkingDay($closestWorkingDay)['date'];
                continue;
            }

            sort($routes);

            $closestDeliveryDate = array_shift($routes);
            $timeDeparture = $closestDeliveryDate;
            $timeDeparture->setTime((int)$routesArrivals[$closestDeliveryDate->getTimestamp()]->format('G'), (int)$routesArrivals[$closestDeliveryDate->getTimestamp()]->format('i'));
            $result = $timeDeparture;
        } while (false === $result);

        return $result;
    }

    /**
     * @param int $warehouseId
     * @param DateTime $date
     * @return DateTime
     */
    public function getClosestDeliveryDate(int $warehouseId, DateTime $date = null): DateTime
    {
        if (null === $date) {
            $currentDate = new DateTime();
        } else {
            $currentDate = clone $date;
        }

        $oneDayInterval = new DateInterval('P1D');
        $result = false;
        $cycleCount = 0;
        $closestWorkingDay = $this->getClosestWorkingDay($currentDate)['date'];
        $closestWorkingDay->setTime((int)$currentDate->format('G'), (int)$currentDate->format('i'));

        do {
            if (100500 === ++$cycleCount) {
                die(var_dump(__METHOD__));
            }

            $routes = [];
            $routesArrivals = [];

            foreach ($this->deliveryDepartures as $deliveryRoute) {
                /* @var $deliveryRoute DeliveryRouteSheet */
                if ($warehouseId !== $deliveryRoute->getWarehouseTo()->getId()) {
                    continue;
                }

                /* @var $timeDeparture DateTime */
                $timeDeparture = clone $deliveryRoute->getTimeDeparture();
                $timeDeparture->setDate((int)$closestWorkingDay->format('Y'), (int)$closestWorkingDay->format('n'), (int)$closestWorkingDay->format('j'));

                if ($closestWorkingDay < $timeDeparture) {
                    $routes[] = $timeDeparture;
                    $routesArrivals[$timeDeparture->getTimestamp()] = $deliveryRoute->getTimeArrival();
                }
            }

            if (!$routes) {
                $closestWorkingDay->add($oneDayInterval);
                $closestWorkingDay = $this->getClosestWorkingDay($closestWorkingDay)['date'];
                continue;
            }

            sort($routes);

            $closestDeliveryDate = array_shift($routes);
            $timeDeparture = $closestDeliveryDate;
            $timeDeparture->setTime((int)$routesArrivals[$closestDeliveryDate->getTimestamp()]->format('G'), (int)$routesArrivals[$closestDeliveryDate->getTimestamp()]->format('i'));
            $result = $timeDeparture;
        } while (false === $result);

        return $result;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return sprintf('%s (#%d)', $this->name, $this->id);
    }
}
