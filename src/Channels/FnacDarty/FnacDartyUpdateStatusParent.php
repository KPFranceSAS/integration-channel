<?php

namespace App\Channels\FnacDarty;

use App\Channels\FnacDarty\FnacDartyApi;
use App\Entity\WebOrder;
use App\Service\Aggregator\UpdateStatusParent;


abstract class FnacDartyUpdateStatusParent extends UpdateStatusParent
{
    protected function getFnacDartyApi(): FnacDartyApi
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

        $result = $this->getFnacDartyApi()->markOrderFulfilled(
            $order->getExternalNumber(),
            $codeCarrier,
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

    abstract protected function getCodeCarrier(string $codeCarrier): ?string;
}
