<?php

namespace App\Channels\ManoMano;

use App\Channels\ManoMano\ManoManoApiParent;
use App\Entity\WebOrder;
use App\Service\Aggregator\UpdateStatusParent;
use App\Service\Carriers\DhlGetTracking;

abstract class ManoManoUpdateStatusParent extends UpdateStatusParent
{
    protected function getManoManoApi(): ManoManoApiParent
    {
        return $this->getApi();
    }


    protected function postUpdateStatusDelivery(WebOrder $order, $invoice, $trackingNumber=null)
    {
        $codeCarrier = $this->getCodeCarrier($order->getCarrierService());
            
        if (!$codeCarrier) {
            $this->addLogToOrder($order, 'Carrier code is not setup for channel ' . $order->getCarrierService());
            return false;
        }

        $result = $this->getManoManoApi()->markOrderAsFulfill(
            $order->getExternalNumber(),
            $codeCarrier,
            $codeCarrier,
            $this->trackingAggregator->getTrackingUrlBase($order->getCarrierService(), $trackingNumber),
            $trackingNumber
        );
        if (!$result) {
            $this->addLogToOrder($order, 'Mark as fulfilled on '.$this->getChannel());
            return true;
        } else {
            $this->addLogToOrder($order, 'Error posting tracking number ' . $trackingNumber.' >>> '.json_encode($result));
            return false;
        }
    
    }



    protected function getCodeCarrier(string $carrierCode): ?string
    {
        if($carrierCode ==  WebOrder::CARRIER_DHL) {
            return "DHL Parcel";
        } elseif ($carrierCode ==  WebOrder::CARRIER_UPS) {
            return "UPS";
        } elseif ($carrierCode ==  WebOrder::CARRIER_DBSCHENKER) {
            return "DB Schenker";
        }
        return null;
    }

}
