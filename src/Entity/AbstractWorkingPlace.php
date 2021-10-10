<?php

declare(strict_types=1);

namespace App\Entity;

use DateInterval;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(
 *   name="discriminator",
 *   type="string",
 * )
 * @ORM\Table(
 *   name="working_place",
 * )
 */
abstract class AbstractWorkingPlace
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
     *   targetEntity="WorkingSchedule",
     *   mappedBy="working_place",
     *   cascade={"persist", "remove"}
     * )
     */
    protected $workingSchedule;

    /**
     * @ORM\OneToMany(
     *   targetEntity="WorkingExtraDay",
     *   mappedBy="working_place",
     *   cascade={"persist", "remove"}
     * )
     */
    protected $workingExtraDays;

    public function __construct()
    {
        $this->workingSchedule = new ArrayCollection();
        $this->workingExtraDays = new ArrayCollection();
    }

    public function getWorkingSchedule()
    {
        return $this->workingSchedule;
    }

    public function getWorkingExtraDays()
    {
        return $this->workingExtraDays;
    }

    /**
     * @param WorkingSchedule $schedule
     * @return self
     */
    public function addWorkingSchedule(WorkingSchedule $schedule): self
    {
        $this->workingSchedule->add($schedule);

        return $this;
    }

    /**
     * @param WorkingExtraDay $extraDay
     * @return self
     */
    public function addWorkingExtraDay(WorkingExtraDay $extraDay): self
    {
        $this->workingExtraDays->add($extraDay);

        return $this;
    }

    /**
     * @param DateTime $date
     * @param int $weekDayNumber
     * @return array
     */
    public function getClosestWorkingDay(DateTime $date = null, int $weekDayNumber = null): array
    {
        if (null === $date) {
            $currentDate = new DateTime();
        } else {
            $currentDate = clone $date;
            $currentDate->setTime(0, 0);
        }

        $oneDayInterval = new DateInterval('P1D');
        $result = false;
        $cycleCount = 0;

        do {
            if (100500 === ++$cycleCount) {
                die(var_dump(__METHOD__));
            }

            $dateWeekDayNumber = (int)$currentDate->format('N');

            if (null !== $weekDayNumber && $weekDayNumber !== $dateWeekDayNumber) {
                $currentDate->add($oneDayInterval);
                continue;
            }

            foreach ($this->workingExtraDays as $extraDay) {
                /* @var $extraDay WorkingExtraDay */
               if ($currentDate != $extraDay->getDate()) {
                   continue;
               }

               if (!$extraDay->getIsWorking()) {
                   $currentDate->add($oneDayInterval);
                   continue 2;
               }

               $result = [
                   'date' => $currentDate,
                   'time_from' => $extraDay->getTimeFrom()->format('H:i'),
                   'time_to' => $extraDay->getTimeTo()->format('H:i'),
               ];

               break 2;
            }

            foreach ($this->workingSchedule as $scheduleDay) {
                /* @var $scheduleDay WorkingSchedule */
               if ($dateWeekDayNumber !== $scheduleDay->getDayNumber()) {
                   continue;
               }

               $result = [
                   'date' => $currentDate,
                   'time_from' => $scheduleDay->getTimeFrom()->format('H:i'),
                   'time_to' => $scheduleDay->getTimeTo()->format('H:i'),
               ];

               break 2;
            }

            $currentDate->add($oneDayInterval);
        } while (false === $result);

        return $result;
    }
}
