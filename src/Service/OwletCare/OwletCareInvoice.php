<?php

namespace App\Service\OwletCare;


use App\Entity\WebOrder;
use App\Service\BusinessCentral\BusinessCentralAggregator;
use App\Service\Carriers\GetTracking;
use App\Service\Invoice\InvoiceParent;
use App\Service\MailService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;


class OwletCareInvoice extends InvoiceParent
{

    private $owletCareApi;


    public function __construct(
        ManagerRegistry $manager,
        LoggerInterface $logger,
        MailService $mailer,
        OwletCareApi $owletCareApi,
        BusinessCentralAggregator $businessCentralAggregator,
        GetTracking $tracker
    ) {
        parent::__construct($manager, $logger, $mailer, $businessCentralAggregator, $tracker);
        $this->owletCareApi = $owletCareApi;
    }

    public function getChannel()
    {
        return WebOrder::CHANNEL_OWLETCARE;
    }




    protected function postInvoice(WebOrder $order, $invoice)
    {
        $tracking = $this->getTracking($order, $invoice);
        if (!$tracking) {
            $this->logger->info('Not found tracking for invoice ' . $invoice['number']);
        } else {
            $this->addLogToOrder($order, 'Order was fulfilled by ' . $tracking['Carrier'] . " with tracking number " . $tracking['Tracking number']);
            $jsonOrder = $order->getOrderContent();
            $mainLocation = $this->owletCareApi->getMainLocation();
            foreach ($jsonOrder['line_items'] as $item) {
                $ids[] = ['id' => $item['id']];
            }
            $result = $this->owletCareApi->markAsFulfilled($jsonOrder['id'], $mainLocation['id'], $ids, $tracking['Tracking number'], 'https://clientesparcel.dhl.es/LiveTracking/ModificarEnvio/' . $tracking['Tracking number']);
            if ($result) {
                $this->addLogToOrder($order, 'Mark as fulfilled on Aliexpress');
                return true;
            } else {
                $this->addLogToOrder($order, 'Error posting tracking number ' . $tracking['Tracking number']);
            }
        }
        return true;
    }
}
