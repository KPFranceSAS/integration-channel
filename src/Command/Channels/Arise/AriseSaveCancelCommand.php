<?php

namespace App\Command\Channels\Arise;

use App\Command\Integrator\SaveCancelCommand;
use App\Entity\WebOrder;

abstract class AriseSaveCancelCommand extends SaveCancelCommand
{
    protected function checkOrderStatus(WebOrder $webOrder)
    {
        $orderArise = $this->getApi()->getOrder($webOrder->getExternalNumber());
        foreach ($orderArise->lines as $line) {
            if ($line->status == 'canceled') {
                $this->cancelSaleOrder($webOrder, $line->cancel_return_initiator. '>'.$line->reason);
                return;
            }
        }
    }
}
