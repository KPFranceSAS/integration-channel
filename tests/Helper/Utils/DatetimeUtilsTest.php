<?php

namespace App\Tests\Helper\Utils;

use App\Helper\Utils\DatetimeUtils;
use DateTime;
use PHPUnit\Framework\TestCase;
use Umulmrum\Holiday\HolidayCalculator;
use Umulmrum\Holiday\Provider\Spain\Spain;

class DatetimeUtilsTest extends TestCase
{
    public function testOutDelayTrueNormalWeek(): void
    {

        $dateTime = new DateTime("2022-03-21 12:00");
        $dateTimeToCheck = new DateTime("2022-03-22 14:00");

        $outOfDelay = DatetimeUtils::isOutOfDelay($dateTime, 24, $dateTimeToCheck);
        $this->assertTrue($outOfDelay);
    }


    public function testOutDelayFalseNormalWeek(): void
    {
        $dateTime = new DateTime("2022-03-21 12:00");
        $dateTimeToCheck = new DateTime("2022-03-22 14:00");

        $outOfDelay = DatetimeUtils::isOutOfDelay($dateTime, 36, $dateTimeToCheck);
        $this->assertFalse($outOfDelay);
    }


    public function testNowDelayFalseNormalWeek(): void
    {
        $dateTime = new DateTime();
        $dateTime->sub(new \DateInterval('PT2H'));
        $dateTimeToCheck = new DateTime("2022-03-22 14:00");

        $outOfDelay = DatetimeUtils::isOutOfDelay($dateTime, 36, $dateTimeToCheck);
        $this->assertFalse($outOfDelay);
    }


    public function testOutDelayTrueWeekEnd(): void
    {

        $dateTime = new DateTime("2022-03-25 14:00");
        $dateTimeToCheck = new DateTime("2022-03-28 18:00");

        $outOfDelay = DatetimeUtils::isOutOfDelay($dateTime, 24, $dateTimeToCheck);
        $this->assertTrue($outOfDelay);
    }



    public function testOutDelayFalseWeekEnd(): void
    {

        $dateTime = new DateTime("2022-03-28 14:00");
        $dateTimeToCheck = new DateTime("2022-03-28 18:00");

        $outOfDelay = DatetimeUtils::isOutOfDelay($dateTime, 30, $dateTimeToCheck);
        $this->assertFalse($outOfDelay);
    }


    public function testOutEasterdaysTrueWeekEnd(): void
    {

        $dateTime = new DateTime("2022-04-13 14:00");
        $dateTimeToCheck = new DateTime("2022-04-19 20:30");

        $outOfDelay = DatetimeUtils::isOutOfDelay($dateTime, 30, $dateTimeToCheck);
        $this->assertTrue($outOfDelay);
    }



    public function testOutEasterdaysFalseWeekEnd(): void
    {

        $dateTime = new DateTime("2022-04-13 14:00");
        $dateTimeToCheck = new DateTime("2022-04-18 18:00");

        $outOfDelay = DatetimeUtils::isOutOfDelay($dateTime, 30, $dateTimeToCheck);
        $this->assertFalse($outOfDelay);
    }


    public function testOutFirstWeekendTrueWeekEnd(): void
    {

        $dateTime = new DateTime("2022-03-26 14:00");
        $dateTimeToCheck = new DateTime("2022-03-28 20:00");

        $outOfDelay = DatetimeUtils::isOutOfDelay($dateTime, 30, $dateTimeToCheck);
        $this->assertFalse($outOfDelay);
    }

    public function testOutFirstWeekendTrueWeekEndMinutes(): void
    {

        $dateTime = new DateTime("2022-03-26 14:00");
        $dateTimeToCheck = new DateTime("2022-03-28 20:01");

        $outOfDelay = DatetimeUtils::isOutOfDelay($dateTime, 30, $dateTimeToCheck);
        $this->assertTrue($outOfDelay);
    }

    public function testOutFirstWeekendFalseWeekEnd(): void
    {

        $dateTime = new DateTime("2022-03-26 14:00");
        $dateTimeToCheck = new DateTime("2022-03-28 18:00");

        $outOfDelay = DatetimeUtils::isOutOfDelay($dateTime, 30, $dateTimeToCheck);
        $this->assertFalse($outOfDelay);
    }
}
