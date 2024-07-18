<?php

namespace App\Service\Carriers;


class CblLogisticTracking
{



    public static function getTrackingUrlBase($trackingCode, $postCode)
    {
        $trackingCode = str_replace("\\", "", $trackingCode);

        return "http://clientes.cbl-logistica.com/public/consultaenvio.aspx?Id=080100389".$postCode.$trackingCode;
    }

}
