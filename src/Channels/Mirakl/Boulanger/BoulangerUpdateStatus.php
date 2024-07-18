<?php

namespace App\Channels\Mirakl\Boulanger;

use App\Channels\Mirakl\MiraklUpdateStatusParent;
use App\Entity\IntegrationChannel;
use App\Entity\WebOrder;

class BoulangerUpdateStatus extends MiraklUpdateStatusParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_BOULANGER;
    }



    protected function getCodeCarrier(string $carrierCode): ?string
    {
        if ($carrierCode ==  WebOrder::CARRIER_DHL) {
            return "DHL";
        } elseif ($carrierCode ==  WebOrder::CARRIER_UPS) {
            return "UPS";
        }
        return null;
    }


    protected function getNameCarrier(string $carrierCode): ?string
    {
        if ($carrierCode ==  WebOrder::CARRIER_DHL) {
            return "DHL Express";
        } elseif ($carrierCode ==  WebOrder::CARRIER_UPS) {
            return "UPS";
        } elseif ($carrierCode ==  WebOrder::CARRIER_DBSCHENKER) {
            return "DB Schenker";
        }  elseif ($carrierCode ==  WebOrder::CARRIER_CBL) {
            return "CBL Logistic";
        }
        return $carrierCode;
    }
}
