<?php

namespace App\Service\Carriers;

class SeurTracking
{
    public static function getTrackingUrlBase($codeTracking)
    {
        return "http://www.seur.com/livetracking/pages/seguimiento-online-busqueda.do?faces-redirect=true&includeViewParams=true&&segOnlineIdentificador=".$codeTracking;
    }
}
