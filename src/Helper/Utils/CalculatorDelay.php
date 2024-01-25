<?php

namespace App\Helper\Utils;

use DateTime;
use DateTimeInterface;


/**
 * Class Calculator
 */
class CalculatorDelay
{
    final public const MONDAY    = 1;
    final public const TUESDAY   = 2;
    final public const WEDNESDAY = 3;
    final public const THURSDAY  = 4;
    final public const FRIDAY    = 5;
    final public const SATURDAY  = 6;
    final public const SUNDAY    = 7;

    final public const WEEK_DAY_FORMAT = 'N';
    final public const HOLIDAY_FORMAT  = 'm-d';
    final public const FREE_DAY_FORMAT = 'Y-m-d';

    /** @var \DateTimeInterface */
    private $date;

    /** @var DateTime[] */
    private $holidays = [];

    /** @var DateTime[] */
    private $freeDays = [];

    /** @var int[] */
    private $freeWeekDays = [];

    /**
     * @param DateTime $startDate Date to start calculations from
     *
     * @return $this
     */
    public function setStartDate(DateTimeInterface $startDate)
    {
        // Use clone so parameter is not passed as a reference.
        // If not, it can brake caller method by changing $startDate parameter while changing it here.

        $this->date = clone $startDate;

        return $this;
    }

    /**
     * @param DateTime[] $holidays Array of holidays that repeats each year. (Only month and date is used to match).
     *
     * @return $this
     */
    public function setHolidays(array $holidays)
    {
        $this->holidays = $holidays;

        return $this;
    }

    /**
     * @return DateTime[]
     */
    private function getHolidays()
    {
        return $this->holidays;
    }

    /**
     * @param DateTime[] $freeDays Array of free days that dose not repeat.
     *
     * @return $this
     */
    public function setFreeDays(array $freeDays)
    {
        $this->freeDays = $freeDays;

        return $this;
    }

    /**
     * @return DateTime[]
     */
    private function getFreeDays()
    {
        return $this->freeDays;
    }

    /**
     * @param int[] $freeWeekDays Array of days of the week which are not business days.
     *
     * @return $this
     */
    public function setFreeWeekDays(array $freeWeekDays)
    {
        $this->freeWeekDays = $freeWeekDays;

        return $this;
    }

    /**
     * @return int[]
     */
    private function getFreeWeekDays()
    {
        if (count($this->freeWeekDays) >= 7) {
            throw new \InvalidArgumentException('Too many non business days provided');
        }

        return $this->freeWeekDays;
    }

    /**
     * @param int $howManyDays
     *
     * @return $this
     */
    public function addBusinessDays($howManyDays)
    {
        $iterator = 0;
        while ($iterator < $howManyDays) {
            $this->getDate()->modify('+1 day');

            if ($this->isBusinessDay($this->getDate())) {
                $iterator++;
            }
        }

        return $this;
    }

    /**
     * @param int $howManyDays
     *
     * @return $this
     */
    public function removeBusinessDays($howManyDays)
    {
        $iterator = 0;
        while ($iterator < $howManyDays) {
            $this->getDate()->modify('-1 day');
            if ($this->isBusinessDay($this->getDate())) {
                $iterator++;
            }
        }

        return $this;
    }




    /**
     * @return DateTime
     */
    public function getDate()
    {
        if ($this->date === null) {
            $this->date = new DateTime();
        }

        return $this->date;
    }

    /**
     * @param DateTime $date
     *
     * @return bool
     */
    public function isBusinessDay(DateTimeInterface $date)
    {
        if ($this->isFreeWeekDayDay($date)) {
            return false;
        }

        if ($this->isHoliday($date)) {
            return false;
        }
        return !$this->isFreeDay($date);
    }

    /**
     * @param DateTime $date
     *
     * @return bool
     */
    public function isFreeWeekDayDay(DateTimeInterface $date)
    {
        $currentWeekDay = (int)$date->format(self::WEEK_DAY_FORMAT);
        return in_array($currentWeekDay, $this->getFreeWeekDays());
    }

    /**
     * @param DateTime $date
     *
     * @return bool
     */
    public function isHoliday(DateTimeInterface $date)
    {
        $holidayFormatValue = $date->format(self::HOLIDAY_FORMAT);
        foreach ($this->getHolidays() as $holiday) {
            if ($holidayFormatValue === $holiday->format(self::HOLIDAY_FORMAT)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param DateTime $date
     *
     * @return bool
     */
    public function isFreeDay(DateTimeInterface $date)
    {
        $freeDayFormatValue = $date->format(self::FREE_DAY_FORMAT);
        foreach ($this->getFreeDays() as $freeDay) {
            if ($freeDayFormatValue === $freeDay->format(self::FREE_DAY_FORMAT)) {
                return true;
            }
        }

        return false;
    }


    public function skipToBegin()
    {
        if (!$this->isBusinessDay($this->getDate())) {
            $this->getDate()->setTime(1, 0, 0);
            while (true) {
                $this->getDate()->modify('+1 day');
                if ($this->isBusinessDay($this->getDate())) {
                    return;
                }
            }
        }
    }
}
