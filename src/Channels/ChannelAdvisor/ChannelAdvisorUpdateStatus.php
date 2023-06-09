<?php

namespace App\Channels\ChannelAdvisor;

use App\Channels\ChannelAdvisor\ChannelAdvisorApi;
use App\Entity\IntegrationChannel;
use App\Entity\IntegrationFile;
use App\Entity\WebOrder;
use App\Service\Aggregator\UpdateStatusParent;

/**
 * Services that will get through the API the order from ChannelAdvisor
 *
 */
class ChannelAdvisorUpdateStatus extends UpdateStatusParent
{
    protected function getChannelApi(): ChannelAdvisorApi
    {
        return $this->getApi();
    }


    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_CHANNELADVISOR;
    }


    protected function postUpdateStatusInvoice(WebOrder $order, $invoice)
    {
        // to remove after integration all old
        $integrationFile = $this->manager->getRepository(IntegrationFile::class)->findOneBy(['externalOrderId'=>$order->getExternalNumber()]);
        if ($integrationFile) {
            $this->addLogToOrder($order, 'Invoice has been already uploaded with old invoice number present in Navision ' . $integrationFile->getDocumentNumber());
            return true;
        }

        $businessCentralConnector   = $this->getBusinessCentralConnector($order->getCompany());
        $this->addLogToOrder($order, 'Retrieve invoice content ' . $invoice['number']);
        $contentPdf  = $businessCentralConnector->getContentInvoicePdf($invoice['id']);
        $this->addLogToOrder($order, 'Retrieved invoice content ' . $invoice['number']);
        $this->addLogToOrder($order, 'Start sending invoice to Channel Advisor');
        $orderApi = $order->getOrderContent();
        $sendFile = $this->getChannelApi()->sendInvoice($orderApi->ProfileID, $orderApi->ID, $invoice['totalAmountIncludingTax'], $invoice['totalTaxAmount'], $invoice['number'], $contentPdf);
        if (!$sendFile) {
            throw new \Exception('Upload  was not done uploaded on ChannelAdvisor for ' . $invoice['number']);
        }
        $this->addLogToOrder($order, 'Invoice uploaded on ChannelAdvisor');
        return true;
    }




    protected function postUpdateStatusDelivery(WebOrder $order, $invoice, $trackingNumber=null)
    {

        $orderApi=$order->getOrderContent();

        $result = $this->getChannelApi()->markOrderAsFulfill(
            $orderApi->ID,
            $trackingNumber,
            $this->trackingAggregator->getTrackingUrlBase($order->getCarrierService(), $trackingNumber),
            $order->getCarrierService()
        );
        if ($result) {
            $businessCentralConnector   = $this->getBusinessCentralConnector($order->getCompany());
            $this->addLogToOrder($order, 'Retrieve invoice content ' . $invoice['number']);
            $contentPdf  = $businessCentralConnector->getContentInvoicePdf($invoice['id']);
            $this->addLogToOrder($order, 'Retrieved invoice content ' . $invoice['number']);
            $this->addLogToOrder($order, 'Start sending invoice to Channel Advisor');
            $sendFile = $this->getChannelApi()->sendInvoice($orderApi->ProfileID, $orderApi->ID, $invoice['totalAmountIncludingTax'], $invoice['totalTaxAmount'], $invoice['number'], $contentPdf);
            if (!$sendFile) {
                throw new \Exception('Upload  was not done uploaded on ChannelAdvisor for ' . $invoice['number']);
            }

            return true;
        } else {
            $this->addLogToOrder($order, 'Error posting tracking number ' . $trackingNumber);
            return false;
        }
    }





}
