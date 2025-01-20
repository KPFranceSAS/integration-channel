<?php

namespace App\Service\Carriers;

use DateTime;
use Exception;
use GuzzleHttp\Client;

class DpdGetTracking
{




    public static function getTrackingUrlBase($codeTracking)
    {
        return "https://www.dpd.fr/trace/".(string) $codeTracking;
    }




}
