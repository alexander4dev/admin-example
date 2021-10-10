<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(
 *   name="working_schedule",
 *   uniqueConstraints={
 *   },
 * )
 */
class WorkingSchedule
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
     *   targetEntity="AbstractWorkingPlace",
     *   inversedBy="workingSchedule",
     * )
     */
    protected $working_place;

    /**
     * @ORM\Column(
     *  type="integer",
     *  options={
     *    "unsigned": true,
     *  }
     * )
     */
    protected $day_number;

    /**
     * @ORM\Column(
     *   type="time",
     *   nullable=false,
     * )
     */
    protected $time_from;

    /**
     * @ORM\Column(
     *   type="time",
     *   nullable=false,
     * )
     */
    protected $time_to;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return AbstractWorkingPlace|null
     */
    public function getWorkingPlace(): ?AbstractWorkingPlace
    {
        return $this->working_place;
    }

    /**
     * @param AbstractWorkingPlace $workingPlace
     * @return self
     */
    public function setWorkingPlace(AbstractWorkingPlace $workingPlace): self
    {
        $this->working_place = $workingPlace;

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

    public function getTimeFrom()
    {
        return $this->time_from;
    }

    public function setTimeFrom($timeFrom): self
    {
        $this->time_from = $timeFrom;

        return $this;
    }

    public function getTimeTo()
    {
        return $this->time_to;
    }

    public function setTimeTo($timeTo): self
    {
        $this->time_to = $timeTo;

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
        return sprintf('%s (%s - %s)', self::WEEK_DAYS[$this->day_number], $this->time_from->format('H:i'), $this->time_to->format('H:i'));
    }
}
