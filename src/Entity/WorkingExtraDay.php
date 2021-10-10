<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(
 *   name="working_extra_day",
 *   uniqueConstraints={
 *   },
 * )
 */
class WorkingExtraDay
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
     *   inversedBy="workingExtraDays",
     * )
     */
    protected $working_place;

    /**
     * @ORM\Column(
     *   type="date",
     *   nullable=false,
     * )
     */
    protected $date;

    /**
     * @ORM\Column(
     *   type="boolean",
     *   nullable=false,
     * )
     */
    protected $is_working;

    /**
     * @ORM\Column(
     *   type="time",
     *   nullable=true,
     * )
     */
    protected $time_from;

    /**
     * @ORM\Column(
     *   type="time",
     *   nullable=true,
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


    public function getDate()
    {
        return $this->date;
    }

    public function setDate($date): self
    {
        $this->date = $date;
//        $this->date->setTime(0, 0);

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getIsWorking(): ?bool
    {
        return $this->is_working;
    }

    /**
     * @param bool $isWorking
     * @return self
     */
    public function setIsWorking(bool $isWorking): self
    {
        $this->is_working = $isWorking;

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

    /**
     * @return string
     */
    public function __toString(): string
    {
        return sprintf('%s (%s)', $this->date->format('d.m.Y'), $this->is_working ? 'Рабочий' : 'Нерабочий');
    }
}
