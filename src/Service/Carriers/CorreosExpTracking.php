<?php

namespace App\Service\Carriers;

class CorreosExpTracking
{
    public static function getTrackingUrlBase($codeTracking)
    {
        return "https://s.correosexpress.com/search?s=".$codeTracking;
    }
}
