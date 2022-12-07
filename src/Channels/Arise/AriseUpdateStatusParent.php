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
        $result = $this->getAriseApi()->markOrderAsFulfill($order->getExternalNumber(), "DHL Parcel Spain", $trackingNumber);
        if ($result) {
            $this->addLogToOrder($order, 'Mark as fulfilled on Arise');
            return true;
        } else {
            $this->addLogToOrder($order, 'Error posting tracking number ' . $trackingNumber);
            return false;
        }
    }



    protected function postUpdateStatusInvoice(WebOrder $order, $invoice)
    {
        $orderArise = $this->getAriseApi()->getOrder($order->getExternalNumber());
        $packageId= null;
        foreach ($orderArise->lines as $line) {
            $packageId = $line->package_id;
        }

        $result = $this->getAriseApi()->markAsReadyToShip($packageId);
        if ($result) {
            $this->addLogToOrder($order, 'Mark as ready to ship to Arise');
            return true;
        } else {
            $this->addLogToOrder($order, 'Error posting Ready to ship');
            return false;
        }
    }
}
