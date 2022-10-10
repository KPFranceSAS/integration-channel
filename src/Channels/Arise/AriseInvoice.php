<?php

namespace App\Channels\Arise;

use App\Entity\WebOrder;
use App\Service\Aggregator\InvoiceParent;
use App\Channels\Arise\AriseApi;

class AriseInvoice extends InvoiceParent
{
    protected function getAriseApi(): AriseApi
    {
        return $this->getApi();
    }


    public function getChannel()
    {
        return WebOrder::CHANNEL_ARISE;
    }


    protected function postInvoice(WebOrder $order, $invoice)
    {
        $tracking = $this->getTracking($order, $invoice);
        if (!$tracking) {
            $this->logger->info('Any tracking found for invoice ' . $invoice['number']);
        } else {
            $this->addOnlyLogToOrderIfNotExists($order, 'Order was fulfilled by ' . $tracking['Carrier'] . " with tracking number " . $tracking['Tracking number']);
            $order->setTrackingUrl('https://clientesparcel.dhl.es/LiveTracking/ModificarEnvio/' . $tracking['Tracking number']);
            $result = $this->getAriseApi()->markOrderAsFulfill($order->getExternalNumber(), "DHL", $tracking['Tracking number']);
            if ($result) {
                $this->addLogToOrder($order, 'Mark as fulfilled on Arise');
                return true;
            } else {
                $orderAliexpress = $this->getAriseApi()->getOrder($order->getExternalNumber());
                /*if ($orderAliexpress->logistics_status == "WAIT_SELLER_SEND_GOODS" && $orderAliexpress->order_status == "IN_CANCEL") {
                    $this->addOnlyErrorToOrderIfNotExists($order, 'Error posting tracking number ' . $tracking['Tracking number'] . ' Customer asks for cancelation and no response was done online. A response should be brought before ' . DatetimeUtils::createStringTimeFromDate($orderAliexpress->over_time_left));
                } else if ($orderAliexpress->order_status == 'FINISH' && $orderAliexpress->order_end_reason == "cancel_order_close_trade") {
                    $this->addOnlyErrorToOrderIfNotExists($order, 'Error posting tracking number ' . $tracking['Tracking number'] . ' Order has been cancelled online on ' . DatetimeUtils::createStringTimeFromDate($orderAliexpress->gmt_trade_end));
                    $order->setStatus(WebOrder::STATE_CANCELLED);
                } else if ($orderAliexpress->order_status == 'FINISH' && $orderAliexpress->order_end_reason == "seller_send_goods_timeout") {
                    $this->addOnlyErrorToOrderIfNotExists($order, 'Error posting tracking number ' . $tracking['Tracking number'] . ' Order has been cancelled online because delay of expedition is out of delay on ' . DatetimeUtils::createStringTimeFromDate($orderAliexpress->gmt_trade_end));
                    $order->setStatus(WebOrder::STATE_CANCELLED);
                } else {
                    $this->addOnlyErrorToOrderIfNotExists($order, 'Error posting tracking number ' . $tracking['Tracking number']);
                }*/
            }
        }
        return false;
    }
}
