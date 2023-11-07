<?php

namespace App\Service\Carriers;

class TntGetTracking
{
    public static function getTrackingUrlBase($codeTracking)
    {
        return "https://www.tnt.com/express/fr_fr/site/outils-expedition/suivi.html?searchType=con&cons=".$codeTracking;
    }
}
