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


    private $tracker;



    public function __construct(
        ManagerRegistry $manager,
        LoggerInterface $logger,
        MailService $mailer,
        AliExpressApi $aliExpressApi,
        BusinessCentralAggregator $businessCentralAggregator,
        GetTracking $tracker
    ) {
        parent::__construct($manager, $logger, $mailer, $businessCentralAggregator);
        $this->aliExpressApi = $aliExpressApi;
        $this->tracker = $tracker;
    }

    public function getChannel()
    {
        return WebOrder::CHANNEL_ALIEXPRESS;
    }


    protected function postInvoice(WebOrder $order, $invoice)
    {
        return true;
        $tracking = $this->tracker->getTracking($order->getCompany(), $invoice['number']);
        if (!$tracking) {
            $this->logger->info('Not found tracking for invoice ' . $invoice['number']);
            return false;
        } else {
            $this->addLogToOrder($order, 'Order was fulfilled by ' . $tracking['Carrier'] . " with tracking number " . $tracking['Tracking number']);
            if ($this->isATrackingNumber($tracking['Tracking number'])) {
                //$result = $this->aliExpressApi->markOrderAsFulfill($order->getExternalNumber(), "SPAIN_LOCAL_DHL", $tracking['Tracking number']);

            } else {
            }

            return false;
        }

        return true;
    }


    private function isATrackingNumber($trackingNumber)
    {
        if (substr($trackingNumber, 0, 5) == 'GALV2') {
            return false;
        }

        return true;
    }
}
