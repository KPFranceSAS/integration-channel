<?php

namespace App\Service\Carriers;

use DateTime;
use Exception;
use GuzzleHttp\Client;

class DpdUkTracking
{





    public static function getGlsResponse($externalOrderNumber, $zipCode): ?array
    {
        return null;
    }


    public static function checkIfDelivered($externalOrderNumber, $zipCode): ?DateTime
    {

        return null;
    }





    public static function getStepsTrackings($externalOrderNumber, $zipCode): ?array
    {
        
        return null;
    }







    public static function getTrackingUrlBase($trackingCode, $postCode)
    {
        return "https://apis.track.dpd.co.uk/v1/track?parcel=$trackingCode&postcode=$postCode";
    }

}
