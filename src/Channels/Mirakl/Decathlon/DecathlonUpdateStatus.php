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



    protected function getCodeCarrier(string $carrierCode): ?string
    {
        if($carrierCode ==  WebOrder::CARRIER_DHL) {
            return "DHLESP";
        } elseif ($carrierCode ==  WebOrder::CARRIER_UPS) {
            return "UPS";
        } elseif ($carrierCode ==  WebOrder::CARRIER_DBSCHENKER) {
            return "DBSchenker";
        }

        
        return null;
    }


    protected function getNameCarrier(string $carrierCode): ?string
    {
        if($carrierCode ==  WebOrder::CARRIER_DHL) {
            return "DHL (Spain)";
        } elseif ($carrierCode ==  WebOrder::CARRIER_UPS) {
            return "UPS";
        } elseif ($carrierCode ==  WebOrder::CARRIER_DBSCHENKER) {
            return "DB Schenker";
        }  elseif ($carrierCode ==  WebOrder::CARRIER_CBL) {
            return "CBL Logistic";
        }
        return null;
    }
}
