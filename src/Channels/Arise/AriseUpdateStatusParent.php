<?php

namespace App\Channels\Arise;

use App\Channels\Arise\AriseApiParent;
use App\Entity\WebOrder;
use App\Service\Aggregator\UpdateStatusParent;

abstract class AriseUpdateStatusParent extends UpdateStatusParent
{
    protected function getAriseApi(): AriseApiParent
    {
        return $this->getApi();
    }


    protected function postUpdateStatusDelivery(WebOrder $order, $invoice, $trackingNumber)
    {
        $result = $this->getAriseApi()->markOrderAsFulfill($order->getExternalNumber(), "DHL", $trackingNumber);
        if ($result) {
            $this->addLogToOrder($order, 'Mark as fulfilled on Arise');
            return true;
        } else {
            $orderArise = $this->getAriseApi()->getOrder($order->getExternalNumber());
            $this->addOnlyErrorToOrderIfNotExists($order, 'Error posting tracking number ' . $trackingNumber);
            return false;
        }
    }
}
