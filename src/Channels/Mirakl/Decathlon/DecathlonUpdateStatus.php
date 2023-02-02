<?php

namespace App\Channels\Mirakl\Decathlon;

use App\Channels\Mirakl\MiraklUpdateStatusParent;
use App\Entity\IntegrationChannel;
use App\Entity\WebOrder;

class DecathlonUpdateStatus extends MiraklUpdateStatusParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_DECATHLON;
    }



    protected function getCodeCarrier(string $carrierCode): ?string {
        if($carrierCode ==  WebOrder::CARRIER_DHL){
            return "DHLESP";
        }
        return null;
    }


    protected function getNameCarrier(string $carrierCode): ?string {
        if($carrierCode ==  WebOrder::CARRIER_DHL){
            return "DHL (Spain)";
        }
        return null;
    }
}
