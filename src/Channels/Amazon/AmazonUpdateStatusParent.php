<?php

namespace App\Channels\Amazon;

use App\Channels\Amazon\AmazonApiParent;
use App\Entity\WebOrder;
use App\Service\Aggregator\UpdateStatusParent;

abstract class AmazonUpdateStatusParent extends UpdateStatusParent
{
    protected function getAmazonApi(): AmazonApiParent
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

        $result = $this->getAmazonApi()->markOrderAsFulfill(
            $order->getExternalNumber(),
            $codeCarrier,
            $codeCarrier,
            $this->trackingAggregator->getTrackingUrlBase($order->getCarrierService(), $trackingNumber),
            str_replace("/", "-", (string) $trackingNumber)
        );
        if ($result) {
            $this->addLogToOrder($order, 'Mark as fulfilled on '.$this->getChannel());
            return true;
        } else {
            $this->addLogToOrder($order, 'Error posting tracking number ' . $trackingNumber);
            return false;
        }
    
    }




    protected function postUpdateStatusInvoice(WebOrder $order, $invoice)
    {
        return $this->sendInvoice($order, $invoice);
    }




    public function sendInvoice(WebOrder $order, $invoice)
    {
        $businessCentralConnector   = $this->getBusinessCentralConnector($order->getCompany());
        $this->addLogToOrder($order, 'Retrieve invoice content ' . $invoice['number']);
        $contentPdf  = $businessCentralConnector->getContentInvoicePdf($invoice['id']);
        $this->addLogToOrder($order, 'Retrieved invoice content ' . $invoice['number']);
        $this->addLogToOrder($order, 'Start sending invoice to Channel Advisor');


        $sendFile = $this->getAmazonApi()->sendInvoice($orderApi->ProfileID, $orderApi->ID, $invoice['totalAmountIncludingTax'], $invoice['totalTaxAmount'], $invoice['number'], $contentPdf);
        if (!$sendFile) {
            throw new \Exception('Upload  was not done uploaded on ChannelAdvisor for ' . $invoice['number']);
        }
    }



            



    protected function getCodeCarrier(string $carrierCode): ?string
    {
        if ($carrierCode ==  WebOrder::CARRIER_DHL) {
            return "DHL";
        } elseif ($carrierCode ==  WebOrder::CARRIER_UPS) {
            return "UPS";
        } elseif ($carrierCode ==  WebOrder::CARRIER_DBSCHENKER) {
            return "DB Schenker";
        } elseif ($carrierCode ==  WebOrder::CARRIER_SENDING) {
            return "Sending";
        }
        return null;
    }

}
