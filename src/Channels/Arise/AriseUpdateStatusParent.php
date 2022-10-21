<?php

namespace App\Channels\Arise;

use App\Channels\Arise\AriseApiParent;
use App\Entity\WebOrder;
use App\Service\Aggregator\UpdateStatusParent;

abstract class AriseUpdateStatusParent extends UpdateStatusParent
{
    protected function getAriseApi(): AriseApiParent
    {
        return $this->getApi();
    }


    protected function postUpdateStatusDelivery(WebOrder $order, $invoice, $trackingNumber)
    {
        $result = $this->getAriseApi()->markOrderAsFulfill($order->getExternalNumber(), "DHL", $trackingNumber);
        if ($result) {
            $this->addLogToOrder($order, 'Mark as fulfilled on Arise');
            return true;
        } else {
            $orderArise = $this->getAriseApi()->getOrder($order->getExternalNumber());
            $this->addOnlyErrorToOrderIfNotExists($order, 'Error posting tracking number ' . $trackingNumber);
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
            return false;
        }
    }
}
