<?php

namespace App\Service\ChannelAdvisor;


use App\Entity\WebOrder;
use App\Service\BusinessCentral\BusinessCentralAggregator;
use App\Service\Carriers\GetTracking;
use App\Service\ChannelAdvisor\ChannelWebservice;
use App\Service\Invoice\InvoiceParent;
use App\Service\MailService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;


/**
 * Services that will get through the API the order from ChannelAdvisor
 * 
 */
class SendInvoicesToChannelAdvisor extends InvoiceParent
{

    private $channel;

    public function __construct(
        ManagerRegistry $manager,
        LoggerInterface $logger,
        MailService $mailer,
        ChannelWebservice $channel,
        BusinessCentralAggregator $businessCentralAggregator,
        GetTracking $tracker
    ) {
        parent::__construct($manager, $logger, $mailer, $businessCentralAggregator, $tracker);
        $this->channel = $channel;
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
        $sendFile = $this->channel->sendInvoice($orderApi->ProfileID, $orderApi->ID, $invoice['totalAmountIncludingTax'], $invoice['totalTaxAmount'], $invoice['number'], $contentPdf);
        if (!$sendFile) {
            throw new \Exception('Upload  was not done uploaded on ChannelAdvisor for ' . $invoice['number']);
        }
        $this->addLogToOrder($order, 'Invoice sent to channel Advisor');
        return true;
    }
}
