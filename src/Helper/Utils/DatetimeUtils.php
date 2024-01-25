<?php

namespace App\Helper\Utils;

use App\Helper\Utils\CalculatorDelay;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Umulmrum\Holiday\HolidayCalculator;
use Umulmrum\Holiday\Provider\Spain\Spain;

class DatetimeUtils
{
    /**
     * @return \DateTime|\DateTimeImmutable
     */
    public static function transformFromIso8601(string $value): DateTime
    {
        $date = explode('T', $value);
        return DateTime::createFromFormat('Y-m-d H:i', $date[0] . ' ' . substr($date[1], 0, 5));
    }


    

    public static function createDateTimeFromDateWithDelay(string $date, int $addHour = 8, $format='Y-m-d H:i:s'): DateTime
    {
        $dateTime = DateTime::createFromFormat($format, $date);
        if($addHour > 0) {
            $dateTime->add(new DateInterval('PT'.$addHour.'H'));
        }
        return $dateTime;
    }


    public static function createDateTimeFromDate(string $date, $format='Y-m-d H:i:s'): DateTime
    {
        $dateTime = DateTime::createFromFormat($format, $date);
        return $dateTime;
    }


    public static function createStringTimeFromDate(string $date, $format = 'd-m-Y H:i'): string
    {
        $dateTime = self::createDateTimeFromDate($date);
        return $dateTime ? $dateTime->format($format) : $date;
    }

    public static function getChoicesWeekDayName(): array
    {
        return [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday'
         ];
    }



    public static function getDayName($numberDay): string
    {
        $days = self::getChoicesWeekDayName();
        return array_key_exists($numberDay, $days) ? $days[$numberDay] : null;
    }


 



    public static function isOutOfDelayBusinessDays(DateTimeInterface $date, int $nbHours, ?DateTimeInterface $toverify = null, $withHolidays = true): bool
    {
        if ($date instanceof DateTimeImmutable) {
            $date = DateTime::createFromImmutable($date);
        }
        $toverify ??= new DateTime();
        if ($toverify instanceof DateTimeImmutable) {
            $toverify = DateTime::createFromImmutable($toverify);
        }
        $calculator = new CalculatorDelay();
        $calculator->setStartDate($date);
        if ($withHolidays) {
            $holidays = self::getHolidaysFrom($date);

            $calculator->setFreeWeekDays([
                CalculatorDelay::SATURDAY,
                CalculatorDelay::SUNDAY
            ]);

            $calculator->setHolidays($holidays);
            $calculator->skipToBegin();
        }

       
        $nbDays = intdiv($nbHours, 24);
        if ($nbDays > 0) {
            $calculator->addBusinessDays($nbDays);
        }
        $dateLimit =  $calculator->getDate();
        

        $nbHoursRestant = $nbHours % 24;
        if ($nbHoursRestant > 0) {
            $dateLimit->add(new DateInterval('PT' . $nbHoursRestant . 'H'));
        }
        return $dateLimit < $toverify;
    }


    public static function isOutOfDelay(DateTimeInterface $date, int $nbHours, ?DateTimeInterface $toverify = null): bool
    {
        return self::isOutOfDelayBusinessDays($date, $nbHours, $toverify, false);
    }


    public static function isOutOfDelayDays(DateTimeInterface $date, int $nbDays, ?DateTimeInterface $toverify = null): bool
    {
        return self::isOutOfDelayBusinessDays($date, $nbDays*24, $toverify, false);
    }


    public static function getHolidaysFrom(DateTimeInterface $date)
    {
        $holidayDateTime = [];
        $years = [];
        $year1 = $date->format('Y');
        $year2 = $year1 + 1;

        $years[] = (int)$year1;
        $years[] = (int)$year2;

        $holidayCalculator = new HolidayCalculator();
        $holidays = $holidayCalculator->calculate(Spain::class, $years);
        foreach ($holidays as $holiday) {
            $holidayDateTime[] = $holiday->getDate();
        }
        return $holidayDateTime;
    }



    public static function getDateOutOfDelayBusinessDaysFrom(int $nbHours, ?DateTimeInterface $toverify = null, $withBusineesDays = true): DateTimeInterface
    {
        $date =  $toverify ?? new DateTime();
        if ($date instanceof DateTimeImmutable) {
            $date = DateTime::createFromImmutable($date);
        }

        $calculator = new CalculatorDelay();
        $calculator->setStartDate($date);
        if ($withBusineesDays) {
            $holidays = self::getHolidaysFrom($date);
            $calculator->setFreeWeekDays([
                CalculatorDelay::SATURDAY,
                CalculatorDelay::SUNDAY
            ]);

            $calculator->setHolidays($holidays);
            $calculator->skipToBegin();
        }


        $nbDays = intdiv($nbHours, 24);
        if ($nbDays > 0) {
            $calculator->removeBusinessDays($nbDays);
        }

        $dateLimit =  $calculator->getDate();

        $nbHoursRestant = $nbHours % 24;
        if ($nbHoursRestant > 0) {
            $dateLimit->sub(new DateInterval('PT' . $nbHoursRestant . 'H'));
        }
        return $dateLimit;
    }


    public static function getDateOutOfDelay(int $nbHours, ?DateTimeInterface $toverify = null): DateTimeInterface
    {
        return self::getDateOutOfDelayBusinessDaysFrom($nbHours, $toverify, false);
    }
}
