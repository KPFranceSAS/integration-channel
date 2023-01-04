<?php

namespace App\Channels\Arise;

use App\Channels\Arise\AriseApiParent;
use App\Entity\WebOrder;
use App\Service\Aggregator\UpdateStatusParent;
use App\Service\Carriers\AriseTracking;

abstract class AriseUpdateStatusParent extends UpdateStatusParent
{
    protected function getAriseApi(): AriseApiParent
    {
        return $this->getApi();
    }


    protected function postUpdateStatusDelivery(WebOrder $order, $invoice, $trackingNumber=null)
    {
        if ($order->getCarrierService() == WebOrder::CARRIER_DHL) {
            $result = $this->getAriseApi()->markOrderAsFulfill($order->getExternalNumber(), "DHL Parcel Spain", $trackingNumber);
            if ($result) {
                $this->addLogToOrder($order, 'Mark as fulfilled on Arise');
                return true;
            } else {
                $this->addLogToOrder($order, 'Error posting tracking number ' . $trackingNumber);
                return false;
            }
        } elseif ($order->getCarrierService() == WebOrder::CARRIER_ARISE) {
            $orderArise = $this->getAriseApi()->getOrder($order->getExternalNumber());
            $packageId= null;
            $trackingCode= null;
            foreach ($orderArise->lines as $line) {
                $packageId = $line->package_id;
                $trackingCode = $line->tracking_code;
            }

            if($trackingCode){
                $order->setTrackingCode($trackingCode);
                $postCode =$orderArise->address_shipping->post_code;
                $order->setTrackingUrl(AriseTracking::getTrackingUrlBase($trackingCode, $postCode));
            }
           

            $result = $this->getAriseApi()->markAsReadyToShip($packageId);
            if ($result) {
                $this->addLogToOrder($order, 'Mark as ready to ship to Arise');
                return true;
            } else {
                $this->addLogToOrder($order, 'Error posting Ready to ship');
                return false;
            }
            
        } else {
            $this->addLogToOrder($order, 'Carrier Unknown '.$order->getCarrierService());
            return false;
        }
    }
}
