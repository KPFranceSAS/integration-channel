<?php

namespace App\Tests\Helper\Utils;

use App\Helper\Utils\DatetimeUtils;
use DateTime;
use PHPUnit\Framework\TestCase;


class DatetimeUtilsTest extends TestCase
{
    public function testOutDelayBusinessDelayTrueNormalWeek(): void
    {

        $dateTime = new DateTime("2022-03-21 12:00");
        $dateTimeToCheck = new DateTime("2022-03-22 14:00");

        $outOfDelay = DatetimeUtils::isOutOfDelayBusinessDays($dateTime, 24, $dateTimeToCheck);
        $this->assertTrue($outOfDelay);
    }


    public function testOutDelayBusinessDelayFalseNormalWeek(): void
    {
        $dateTime = new DateTime("2022-03-21 12:00");
        $dateTimeToCheck = new DateTime("2022-03-22 14:00");

        $outOfDelay = DatetimeUtils::isOutOfDelayBusinessDays($dateTime, 36, $dateTimeToCheck);
        $this->assertFalse($outOfDelay);
    }


    public function testOutDelayBusinessNowDelayFalseNormalWeek(): void
    {
        $dateTime = new DateTime();
        $dateTime->sub(new \DateInterval('PT2H'));
        $dateTimeToCheck = new DateTime("2022-03-22 14:00");

        $outOfDelay = DatetimeUtils::isOutOfDelayBusinessDays($dateTime, 36, $dateTimeToCheck);
        $this->assertFalse($outOfDelay);
    }


    public function testOutDelayBusinessDelayTrueWeekEnd(): void
    {

        $dateTime = new DateTime("2022-03-26 06:00");
        $dateTimeToCheck = new DateTime("2022-03-29 10:00");

        $outOfDelay = DatetimeUtils::isOutOfDelayBusinessDays($dateTime, 24, $dateTimeToCheck);
        $this->assertTrue($outOfDelay);
    }



    public function testOutDelayBusinessDelayFalseWeekEnd(): void
    {

        $dateTime = new DateTime("2022-03-26 06:00");
        $dateTimeToCheck = new DateTime("2022-03-28 20:00");

        $outOfDelay = DatetimeUtils::isOutOfDelayBusinessDays($dateTime, 30, $dateTimeToCheck);
        $this->assertFalse($outOfDelay);
    }



    public function testOutDelayBusinessEasterdaysTrueWeekEnd(): void
    {

        $dateTime = new DateTime("2022-04-13 14:00");
        $dateTimeToCheck = new DateTime("2022-04-19 20:30");

        $outOfDelay = DatetimeUtils::isOutOfDelayBusinessDays($dateTime, 30, $dateTimeToCheck);
        $this->assertTrue($outOfDelay);
    }



    public function testOutDelayBusinessEasterdaysFalseWeekEnd(): void
    {

        $dateTime = new DateTime("2022-04-13 14:00");
        $dateTimeToCheck = new DateTime("2022-04-18 18:00");

        $outOfDelay = DatetimeUtils::isOutOfDelayBusinessDays($dateTime, 30, $dateTimeToCheck);
        $this->assertFalse($outOfDelay);
    }


    public function testOutDelayBusinessFirstWeekendTrueWeekEnd(): void
    {

        $dateTime = new DateTime("2022-03-26 14:00");
        $dateTimeToCheck = new DateTime("2022-03-29 07:00");

        $outOfDelay = DatetimeUtils::isOutOfDelayBusinessDays($dateTime, 30, $dateTimeToCheck);
        $this->assertFalse($outOfDelay);
    }

    public function testOutDelayBusinessFirstWeekendTrueWeekEndMinutes(): void
    {

        $dateTime = new DateTime("2022-03-26 14:00");
        $dateTimeToCheck = new DateTime("2022-03-29 07:01");

        $outOfDelay = DatetimeUtils::isOutOfDelayBusinessDays($dateTime, 30, $dateTimeToCheck);
        $this->assertTrue($outOfDelay);
    }

    public function testOutDelayBusinessFirstWeekendFalseWeekEnd(): void
    {

        $dateTime = new DateTime("2022-03-26 14:00");
        $dateTimeToCheck = new DateTime("2022-03-28 18:00");

        $outOfDelay = DatetimeUtils::isOutOfDelayBusinessDays($dateTime, 30, $dateTimeToCheck);
        $this->assertFalse($outOfDelay);
    }


    public function testOutDelayMinutesTrue(): void
    {
        $dateTime = new DateTime("2022-03-26 14:00");
        $dateTimeToCheck = new DateTime("2022-03-27 14:01");

        $outOfDelay = DatetimeUtils::isOutOfDelay($dateTime, 24, $dateTimeToCheck);
        $this->assertTrue($outOfDelay);
    }

    public function testOutDelayMinutesFalse(): void
    {

        $dateTime = new DateTime("2022-03-26 14:00");
        $dateTimeToCheck = new DateTime("2022-03-27 14:00");

        $outOfDelay = DatetimeUtils::isOutOfDelay($dateTime, 24, $dateTimeToCheck);
        $this->assertFalse($outOfDelay);
    }

    public function testOutDelayMinutesSameFalse(): void
    {

        $dateTime = new DateTime("2022-03-26 14:00");
        $dateTimeToCheck = new DateTime("2022-03-26 14:00");

        $outOfDelay = DatetimeUtils::isOutOfDelay($dateTime, 24, $dateTimeToCheck);
        $this->assertFalse($outOfDelay);
    }
}
