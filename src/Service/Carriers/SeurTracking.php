<?php

namespace App\Service\Carriers;

class SeurTracking
{
    public static function getTrackingUrlBase($codeTracking)
    {
        return "https://www.seur.com/miseur/mis-envios?tracking=".$codeTracking;
    }
}
