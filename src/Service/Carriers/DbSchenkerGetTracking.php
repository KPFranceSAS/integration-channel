<?php

namespace App\Service\Carriers;

use function Symfony\Component\String\u;

class DbSchenkerGetTracking
{
    public static function getTrackingUrlBase($codeTracking)
    {
        return "https://www.dbschenker.com/app/tracking-public/?refNumber=".urlencode($codeTracking)."&refType=ShippersRefNo";
    }




}
