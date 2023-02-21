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
        $nameCarrier = $this->getNameCarrier($order->getCarrierService());
            
        if (!$codeCarrier || !$nameCarrier) {
            $this->addLogToOrder($order, 'Carrier code is not setup for channel ' . $order->getCarrierService());
            return false;
        }

        $result = $this->getManoManoApi()->markOrderAsFulfill(
            $order->getExternalNumber(),
            $codeCarrier,
            $nameCarrier,
            DhlGetTracking::getTrackingUrlBase($trackingNumber),
            $trackingNumber
        );
        if ($result) {
            $this->addLogToOrder($order, 'Mark as fulfilled on '.$this->getChannel());
            return true;
        } else {
            $this->addLogToOrder($order, 'Error posting tracking number ' . $trackingNumber);
            return false;
        }
    }

    protected function getCodeCarrier(string $carrierCode): ?string
    {
        if ($carrierCode ==  WebOrder::CARRIER_DHL) {
            return "DHLESP";
        }
        return null;
    }


    protected function getNameCarrier(string $carrierCode): ?string
    {
        if ($carrierCode ==  WebOrder::CARRIER_DHL) {
            return "DHL (Spain)";
        }
        return null;
    }
}
