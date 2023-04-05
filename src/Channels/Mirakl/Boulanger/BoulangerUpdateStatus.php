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
            return "ES_DHL_Parcel";
        } elseif ($carrierCode ==  WebOrder::CARRIER_UPS) {
            return "ES_UPS";
        }
        return null;
    }


    protected function getNameCarrier(string $carrierCode): ?string
    {
        if ($carrierCode ==  WebOrder::CARRIER_DHL) {
            return "DHL (Spain)";
        } elseif ($carrierCode ==  WebOrder::CARRIER_UPS) {
            return "UPS";
        }
        return null;
    }
}
