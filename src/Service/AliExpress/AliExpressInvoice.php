<?php

namespace App\Service\AliExpress;


use App\Entity\WebOrder;
use App\Service\BusinessCentral\BusinessCentralAggregator;
use App\Service\Carriers\GetTracking;
use App\Service\Invoice\InvoiceParent;
use App\Service\MailService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;


class AliExpressInvoice extends InvoiceParent
{

    private $aliExpressApi;



    public function __construct(
        ManagerRegistry $manager,
        LoggerInterface $logger,
        MailService $mailer,
        AliExpressApi $aliExpressApi,
        BusinessCentralAggregator $businessCentralAggregator,
        GetTracking $tracker
    ) {
        parent::__construct($manager, $logger, $mailer, $businessCentralAggregator, $tracker);
        $this->aliExpressApi = $aliExpressApi;
    }

    public function getChannel()
    {
        return WebOrder::CHANNEL_ALIEXPRESS;
    }


    protected function postInvoice(WebOrder $order, $invoice)
    {
        $tracking = $this->getTracking($order, $invoice);
        if (!$tracking) {
            $this->logger->info('Not found tracking for invoice ' . $invoice['number']);
        } else {
            $this->addLogToOrder($order, 'Order was fulfilled by ' . $tracking['Carrier'] . " with tracking number " . $tracking['Tracking number']);
            $result = $this->aliExpressApi->markOrderAsFulfill($order->getExternalNumber(), "SPAIN_LOCAL_DHL", $tracking['Tracking number']);
            if ($result) {
                $this->addLogToOrder($order, 'Mark as fulfilled on Aliexpress');
                return true;
            } else {
                $this->addLogToOrder($order, 'Error posting tracking number ' . $tracking['Tracking number']);
            }
        }
        return false;
    }
}
