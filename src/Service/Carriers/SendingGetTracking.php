<?php

namespace App\Service\Carriers;

class SendingGetTracking
{
    public static function getTrackingUrlBase($codeTracking)
    {
        return "https://info.sending.es/fgts/pub/locNumServ.seam?web=S&localizador=".$codeTracking;
    }
}
