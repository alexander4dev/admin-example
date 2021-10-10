<?php

declare(strict_types=1);

namespace App\Entity;

use DateInterval;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="supplier")
 */
class Supplier extends AbstractSupplier
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
     * @ORM\OneToMany(
     *   targetEntity="SupplierDeliverySchedule",
     *   mappedBy="supplier",
     *   cascade={"persist", "remove"}
     * )
     */
    protected $deliverySchedule;

    public function __construct()
    {
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
     * @param int $warehouseId
     * @param DateTime $date
     * @return DateTime
     */
    public function getClosestDepartureDate(int $warehouseId, DateTime $date = null): DateTime
    {
        if (null === $date) {
            $initialDate = new DateTime();
        } else {
            $initialDate = clone $date;
        }

        $currentDate = clone $initialDate;
        $oneDayInterval = new DateInterval('P1D');
        $result = false;
        $cycleCount = 0;

        do {
            if (100500 === ++$cycleCount) {
                die(var_dump(__METHOD__));
            }

            $weekDayDeliveries = [];
            $weekDayDeliveriesIntervals = [];
            $currentDeliveryDate = $this->getClosestWorkingDay($currentDate)['date'];
            $currentDeliveryDate->setTime((int)$currentDate->format('G'), (int)$currentDate->format('i'));
            $dateWeekDayNumber = (int)$currentDeliveryDate->format('N');

            foreach ($this->deliverySchedule as $deliveryDay) {
                /* @var $deliveryDay SupplierDeliverySchedule */
                if ($warehouseId !== $deliveryDay->getWarehouse()->getId()) {
                    continue;
                }

                if ($dateWeekDayNumber !== $deliveryDay->getDayNumber()) {
                    continue;
                }

                /* @var $deliveryDate DateTime */
                $deliveryDate = clone $deliveryDay->getOrderTime();
                $deliveryDate->setDate((int)$currentDeliveryDate->format('Y'), (int)$currentDeliveryDate->format('n'), (int)$currentDeliveryDate->format('j'));

                if ($currentDeliveryDate < $deliveryDate || $initialDate < $currentDeliveryDate) {
                    $weekDayDeliveries[] = $deliveryDate;
                    $weekDayDeliveriesIntervals[$deliveryDate->getTimestamp()] = $deliveryDay->getDeliveryTimeAmount();
                }
            }

            if (!$weekDayDeliveries) {
                $currentDeliveryDate->add($oneDayInterval);
                $currentDate = $this->getClosestWorkingDay($currentDeliveryDate)['date'];
                continue;
            }

            sort($weekDayDeliveries);

            $closestDeliveryDate = array_shift($weekDayDeliveries);
            $resultDate = $closestDeliveryDate;
            $result = $resultDate;
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
            $initialDate = new DateTime();
        } else {
            $initialDate = clone $date;
        }

        $currentDate = clone $initialDate;
        $oneDayInterval = new DateInterval('P1D');
        $result = false;
        $cycleCount = 0;

        do {
            if (100500 === ++$cycleCount) {
                die(var_dump(__METHOD__));
            }

            $weekDayDeliveries = [];
            $weekDayDeliveriesIntervals = [];
            $currentDeliveryDate = $this->getClosestWorkingDay($currentDate)['date'];
            $currentDeliveryDate->setTime((int)$currentDate->format('G'), (int)$currentDate->format('i'));
            $dateWeekDayNumber = (int)$currentDeliveryDate->format('N');

            foreach ($this->deliverySchedule as $deliveryDay) {
                /* @var $deliveryDay SupplierDeliverySchedule */
                if ($warehouseId !== $deliveryDay->getWarehouse()->getId()) {
                    continue;
                }

                if ($dateWeekDayNumber !== $deliveryDay->getDayNumber()) {
                    continue;
                }

                /* @var $deliveryDate DateTime */
                $deliveryDate = clone $deliveryDay->getOrderTime();
                $deliveryDate->setDate((int)$currentDeliveryDate->format('Y'), (int)$currentDeliveryDate->format('n'), (int)$currentDeliveryDate->format('j'));

                if ($currentDeliveryDate < $deliveryDate || $initialDate < $currentDeliveryDate) {
                    $weekDayDeliveries[] = $deliveryDate;
                    $weekDayDeliveriesIntervals[$deliveryDate->getTimestamp()] = $deliveryDay->getDeliveryTimeAmount();
                }
            }

            if (!$weekDayDeliveries) {
                $currentDeliveryDate->add($oneDayInterval);
                $currentDate = $this->getClosestWorkingDay($currentDeliveryDate)['date'];
                continue;
            }

            sort($weekDayDeliveries);

            $closestDeliveryDate = array_shift($weekDayDeliveries);
            $resultDate = $closestDeliveryDate;
            $resultDate->add($weekDayDeliveriesIntervals[$closestDeliveryDate->getTimestamp()]);
            $result = $resultDate;
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
