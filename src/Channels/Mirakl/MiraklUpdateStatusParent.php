<?php

namespace App\Channels\Mirakl;

use App\Channels\Mirakl\MiraklApiParent;
use App\Entity\WebOrder;
use App\Service\Aggregator\UpdateStatusParent;
use App\Service\Carriers\DhlGetTracking;

abstract class MiraklUpdateStatusParent extends UpdateStatusParent
{
    protected function getMiraklApi(): MiraklApiParent
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


        $orderApi=$order->getOrderContent();



        $result = $this->getMiraklApi()->markOrderAsFulfill(
            $orderApi['id'],
            $codeCarrier,
            $nameCarrier,
            $this->trackingAggregator->getTrackingUrlBase($order->getCarrierService(), $trackingNumber),
            $trackingNumber
        );
        if ($result) {
            $this->addLogToOrder($order, 'Mark as fulfilled on '.$this->getChannel());

                
            $businessCentralConnector   = $this->getBusinessCentralConnector($order->getCompany());
            $this->addLogToOrder($order, 'Retrieve invoice content ' . $invoice['number']);
            $contentPdf  = $businessCentralConnector->getContentInvoicePdf($invoice['id']);
            $this->addLogToOrder($order, 'Retrieved invoice content ' . $invoice['number']);
                

            $result = $this->getMiraklApi()->sendInvoice($orderApi['id'], $invoice['number'], $contentPdf);
            $this->addLogToOrder($order, 'Invoice ' . $invoice['number'].' uploaded on '.$this->getChannel());

            return true;
        } else {
            $this->addLogToOrder($order, 'Error posting tracking number ' . $trackingNumber);
            return false;
        }
    }

    abstract protected function getCodeCarrier(string $codeCarrier): ?string;

    abstract protected function getNameCarrier(string $codeCarrier): ?string;
}
