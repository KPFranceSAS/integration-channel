<?php

namespace App\Channels\Mirakl\PcComponentes;

use App\Channels\Mirakl\MiraklUpdateStatusParent;
use App\Entity\IntegrationChannel;
use App\Entity\WebOrder;

class PcComponentesUpdateStatus extends MiraklUpdateStatusParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_PCCOMPONENTES;
    }



    protected function getCodeCarrier(string $carrierCode): ?string
    {
        if ($carrierCode ==  WebOrder::CARRIER_DHL) {
            return "dhlparcel-es";
        }
        return null;
    }


    protected function getNameCarrier(string $carrierCode): ?string
    {
        if ($carrierCode ==  WebOrder::CARRIER_DHL) {
            return "DHL";
        } elseif ($carrierCode ==  WebOrder::CARRIER_DBSCHENKER) {
            return "DB Schenker";
        }  elseif ($carrierCode ==  WebOrder::CARRIER_CBL) {
            return "CBL Logistic";
        }
        return $carrierCode;
    }
}
