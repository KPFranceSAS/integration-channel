<?php

namespace App\Service\AliExpress;


use App\Entity\WebOrder;
use App\Helper\Utils\DatetimeUtils;
use App\Service\AliExpress\AliExpressApi;
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
            $this->logger->info('Any tracking found for invoice ' . $invoice['number']);
        } else {
            $this->addLogToOrder($order, 'Order was fulfilled by ' . $tracking['Carrier'] . " with tracking number " . $tracking['Tracking number']);
            $order->setTrackingUrl('https://clientesparcel.dhl.es/LiveTracking/ModificarEnvio/' . $tracking['Tracking number']);
            $result = $this->aliExpressApi->markOrderAsFulfill($order->getExternalNumber(), "SPAIN_LOCAL_DHL", $tracking['Tracking number']);
            if ($result) {
                $this->addLogToOrder($order, 'Mark as fulfilled on Aliexpress');
                return true;
            } else {
                $orderAliexpress = $this->aliExpressApi->getOrder($order->getExternalNumber());
                dump($orderAliexpress);
                dump(DatetimeUtils::createStringTimeFromAliExpressDate($orderAliexpress->over_time_left));
                if ($orderAliexpress->logistics_status == "WAIT_SELLER_SEND_GOODS" && $orderAliexpress->order_status == "IN_CANCEL") {
                    $this->addError($order, 'Error posting tracking number ' . $tracking['Tracking number'] . ' Customer asks for cancelation and no response was done online. A response should be brought before ' . DatetimeUtils::createStringTimeFromAliExpressDate($orderAliexpress->over_time_left));
                } else if ($orderAliexpress->order_status == 'FINISH' && $orderAliexpress->order_end_reason == "cancel_order_close_trade") {
                    $this->addError($order, 'Error posting tracking number ' . $tracking['Tracking number'] . ' Order has been cancelled online on ' . DatetimeUtils::createStringTimeFromAliExpressDate($orderAliexpress->gmt_trade_end));
                    $order->setStatus(WebOrder::STATE_CANCELLED);
                } else if ($orderAliexpress->order_status == 'FINISH' && $orderAliexpress->order_end_reason == "seller_send_goods_timeout") {
                    $this->addError($order, 'Error posting tracking number ' . $tracking['Tracking number'] . ' Order has been cancelled online because delay of expedition is out of delay on ' . DatetimeUtils::createStringTimeFromAliExpressDate($orderAliexpress->gmt_trade_end));
                    $order->setStatus(WebOrder::STATE_CANCELLED);
                } else {
                    $this->addError($order, 'Error posting tracking number ' . $tracking['Tracking number']);
                }
            }
        }
        return false;
    }
}
