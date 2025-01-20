<?php

namespace App\Service\Carriers;

class CorreosTracking
{
    public static function getTrackingUrlBase($codeTracking)
    {
        return "https://www.correos.es/es/en/tools/tracker/items/details?tracking-number=".$codeTracking;
    }
}
