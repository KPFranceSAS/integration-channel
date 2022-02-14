<?php

namespace App\Helper\Utils;

use DateTime;

class DatetimeUtils
{

    public static function transformFromIso8601(string $value): DateTime
    {
        $date = explode('T', $value);
        return DateTime::createFromFormat('Y-m-d H:i:s', $date[0] . ' ' . substr($date[1], 0, 8));
    }
}
