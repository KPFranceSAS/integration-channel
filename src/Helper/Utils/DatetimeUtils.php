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



    public static function isOutOfDelay(DateTimeInterface $date, int $nbHours, ?DateTimeInterface $toverify = null): bool
    {
        $toverify =  $toverify ? $toverify :  new DateTime();

        $holidays = self::getHolidaysFrom($date);
        $calculator = new CalculatorDelay();
        $calculator->setStartDate($date);
        $calculator->setHolidays($holidays);

        $nbDays = intdiv($nbHours, 24);
        if ($nbDays > 0) {
            $calculator->addBusinessDays($nbDays);
        }

        $dateLimit =  $calculator->getDate();

        $nbHoursRestant = $nbHours % 24;
        if ($nbHoursRestant > 0) {
            $dateLimit->add(new DateInterval('PT' . $nbHoursRestant . 'H'));
        }

        var_dump($dateLimit->format('Ymd'));
        return $dateLimit < $toverify;
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
}
