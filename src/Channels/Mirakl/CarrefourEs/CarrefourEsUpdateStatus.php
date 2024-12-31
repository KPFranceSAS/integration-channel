<?php

namespace App\Channels\Mirakl\CarrefourEs;

use App\Channels\Mirakl\MiraklUpdateStatusParent;
use App\Entity\IntegrationChannel;
use App\Entity\WebOrder;

class CarrefourEsUpdateStatus extends MiraklUpdateStatusParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_CARREFOUR_ES;
    }



    protected function getCodeCarrier(string $carrierCode): ?string
    {
        if ($carrierCode ==  WebOrder::CARRIER_DHL) {
            return "C5";
        } elseif ($carrierCode ==  WebOrder::CARRIER_CBL) {
            return "C45";
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
        }  elseif ($carrierCode ==  WebOrder::CARRIER_SENDING) {
            return "Sending";
        }
        return $carrierCode;
    }
}
