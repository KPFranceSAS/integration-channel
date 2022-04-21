<?php

namespace App\Helper\Utils;

use App\Helper\Utils\CalculatorDelay;
use DateInterval;
use DateTime;
use DateTimeInterface;
use Umulmrum\Holiday\HolidayCalculator;
use Umulmrum\Holiday\Provider\Spain\Spain;

class DatetimeUtils
{

    public static function transformFromIso8601(string $value): DateTime
    {
        $date = explode('T', $value);
        return DateTime::createFromFormat('Y-m-d H:i:s', $date[0] . ' ' . substr($date[1], 0, 8));
    }



    public static function createDateTimeFromAliExpressDate(string $date): DateTime
    {
        $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $date);
        $dateTime->add(new DateInterval('PT9H'));
        return $dateTime;
    }


    public static function createStringTimeFromAliExpressDate(string $date, $format = 'd-m-Y H:i'): string
    {
        $dateTime = self::createDateTimeFromAliExpressDate($date);
        return $dateTime ? $dateTime->format($format) : $date;
    }






    public static function isOutOfDelayBusinessDays(DateTimeInterface $date, int $nbHours, ?DateTimeInterface $toverify = null, $withHolidays = true): bool
    {
        $toverify =  $toverify ? $toverify :  new DateTime();
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


    public static function getHolidaysFrom(DateTimeInterface $date)
    {
        $holidayDateTime = [];
        $years = [];
        $year1 = $date->format('Y');
        $year2 = $year1 + 1;

        $years[] = (int)$year1;
        $years[] = (int)$year2;

        $holidayCalculator = new HolidayCalculator();
        $holidays = $holidayCalculator->calculate(Spain::class,  $years);
        foreach ($holidays as $holiday) {
            $holidayDateTime[] = $holiday->getDate();
        }
        return $holidayDateTime;
    }



    public static function getDateOutOfDelayBusinessDaysFrom(int $nbHours, ?DateTimeInterface $toverify = null, $withBusineesDays = true): DateTimeInterface
    {
        $date =  $toverify ? $toverify :  new DateTime();

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
