<?php

namespace App\Channels\FnacDarty\DartyFr;

use App\Channels\FnacDarty\FnacDartyUpdateStatusParent;
use App\Entity\IntegrationChannel;
use App\Entity\WebOrder;

class DartyFrUpdateStatus extends FnacDartyUpdateStatusParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_DARTY_FR;
    }



    protected function getCodeCarrier(string $carrierCode): ?string
    {
        if ($carrierCode ==  WebOrder::CARRIER_DHL) {
            return "DHLES";
        } elseif ($carrierCode ==  WebOrder::CARRIER_UPS) {
            return "UPS";
        } elseif ($carrierCode ==  WebOrder::CARRIER_CORREOSEXP) {
            return "SPAINCORREOSES";
        } elseif ($carrierCode ==  WebOrder::CARRIER_TNT) {
            return "TNTFR";
        } elseif ($carrierCode ==  WebOrder::CARRIER_DBSCHENKER) {
            return "DBSCHENKERSE";
        }
        return "AUTRE";
    }



}
