<?php

namespace App\Channels\FnacDarty\FnacFr;

use App\Channels\FnacDarty\FnacDartyUpdateStatusParent;
use App\Entity\IntegrationChannel;
use App\Entity\WebOrder;

class FnacFrUpdateStatus extends FnacDartyUpdateStatusParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_FNAC_FR;
    }



    protected function getCodeCarrier(string $carrierCode): ?string
    {
        if ($carrierCode ==  WebOrder::CARRIER_DHL) {
            return "DHL";
        } elseif ($carrierCode ==  WebOrder::CARRIER_UPS) {
            return "UPS";
        } elseif ($carrierCode ==  WebOrder::CARRIER_DBSCHENKER) {
            return "AUTRE";
        }
        return null;
    }


    
}
