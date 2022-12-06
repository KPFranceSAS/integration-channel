<?php

namespace App\Command\Channels\AliExpress;

use App\Command\Integrator\SaveCancelCommand;
use App\Entity\WebOrder;
use App\Helper\Utils\DatetimeUtils;

abstract class AliExpressSaveCancelCommand extends SaveCancelCommand
{
    protected function checkOrderStatus(WebOrder $webOrder)
    {
        $orderAliexpress = $this->getApi()->getOrder($webOrder->getExternalNumber());
        if (
            $orderAliexpress->order_status == 'FINISH'
            && $orderAliexpress->order_end_reason == "cancel_order_close_trade"
        ) {
            $reason =  'Order has been cancelled after acceptation  online on '
            . DatetimeUtils::createStringTimeFromDate($orderAliexpress->gmt_trade_end);
            $this->cancelSaleOrder($webOrder, $reason);
        } elseif (
            $orderAliexpress->order_status == 'FINISH'
            && $orderAliexpress->order_end_reason == "seller_send_goods_timeout"
        ) {
            $reason =  'Order has been cancelled online because delay of expedition is out of delay on '
            . DatetimeUtils::createStringTimeFromDate($orderAliexpress->gmt_trade_end);
            $this->cancelSaleOrder($webOrder, $reason);
        }
    }
}
