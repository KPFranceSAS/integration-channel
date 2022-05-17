<?php

namespace App\Service\ChannelAdvisor;


use App\Entity\WebOrder;
use App\Helper\Invoice\InvoiceParent;
use App\Service\ChannelAdvisor\ChannelAdvisorApi;

/**
 * Services that will get through the API the order from ChannelAdvisor
 * 
 */
class ChannelAdvisorInvoice extends InvoiceParent
{


    protected function getChannelApi(): ChannelAdvisorApi
    {
        return $this->getApi();
    }


    public function getChannel()
    {
        return WebOrder::CHANNEL_CHANNELADVISOR;
    }


    protected function postInvoice(WebOrder $order, $invoice)
    {
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
        $this->addLogToOrder($order, 'Invoice sent to channel Advisor');
        return true;
    }
}
