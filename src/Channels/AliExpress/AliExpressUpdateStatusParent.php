<?php

namespace App\Channels\AliExpress;

use App\Channels\AliExpress\AliExpressApiParent;
use App\Entity\WebOrder;
use App\Helper\Utils\DatetimeUtils;
use App\Service\Aggregator\UpdateStatusParent;

abstract class AliExpressUpdateStatusParent extends UpdateStatusParent
{
    protected function getAliExpressApi(): AliExpressApiParent
    {
        return $this->getApi();
    }


    public function getChannel()
    {
        return WebOrder::CHANNEL_ALIEXPRESS;
    }


    protected function postUpdateStatusDelivery(WebOrder $order, $invoice, $trackingNumber)
    {
            $result = $this->getAliExpressApi()->markOrderAsFulfill($order->getExternalNumber(), "SPAIN_LOCAL_DHL", $trackingNumber);
            if ($result) {
                $this->addLogToOrder($order, 'Mark as fulfilled on Aliexpress');
                return true;
            } else {
                $orderAliexpress = $this->getAliExpressApi()->getOrder($order->getExternalNumber());
                if ($orderAliexpress->logistics_status == "WAIT_SELLER_SEND_GOODS" && $orderAliexpress->order_status == "IN_CANCEL") {
                    $this->addOnlyErrorToOrderIfNotExists($order, 'Error posting tracking number ' . $trackingNumber . ' Customer asks for cancelation and no response was done online. A response should be brought before ' . DatetimeUtils::createStringTimeFromDate($orderAliexpress->over_time_left));
                } elseif ($orderAliexpress->order_status == 'FINISH' && $orderAliexpress->order_end_reason == "cancel_order_close_trade") {
                    $this->addOnlyErrorToOrderIfNotExists($order, 'Error posting tracking number ' . $trackingNumber . ' Order has been cancelled online on ' . DatetimeUtils::createStringTimeFromDate($orderAliexpress->gmt_trade_end));
                    $order->setStatus(WebOrder::STATE_CANCELLED);
                } elseif ($orderAliexpress->order_status == 'FINISH' && $orderAliexpress->order_end_reason == "seller_send_goods_timeout") {
                    $this->addOnlyErrorToOrderIfNotExists($order, 'Error posting tracking number ' . $trackingNumber . ' Order has been cancelled online because delay of expedition is out of delay on ' . DatetimeUtils::createStringTimeFromDate($orderAliexpress->gmt_trade_end));
                    $order->setStatus(WebOrder::STATE_CANCELLED);
                } else {
                    $this->addOnlyErrorToOrderIfNotExists($order, 'Error posting tracking number ' . $trackingNumber);
                }
            }
        return false;
    }
}
