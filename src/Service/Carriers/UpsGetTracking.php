<?php

namespace App\Service\Carriers;

use function Symfony\Component\String\u;

class UpsGetTracking
{
    public static function getTrackingUrlBase($codeTracking)
    {
        return "https://wwwapps.ups.com/WebTracking/track?loc=en_IT&trackNums=" . $codeTracking;
    }

}
