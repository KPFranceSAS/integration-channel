<?php

namespace App\Channels\Mirakl;

use App\Channels\Mirakl\MiraklApiParent;
use App\Entity\WebOrder;
use App\Service\Aggregator\UpdateStatusParent;
use App\Service\Carriers\MiraklTracking;

abstract class MiraklUpdateStatusParent extends UpdateStatusParent
{
    protected function getMiraklApi(): MiraklApiParent
    {
        return $this->getApi();
    }


    protected function postUpdateStatusDelivery(WebOrder $order, $invoice, $trackingNumber=null)
    {
    }
}
