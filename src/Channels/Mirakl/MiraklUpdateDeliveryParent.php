<?php

namespace App\Channels\Mirakl;

use App\Channels\Mirakl\MiraklApiParent;
use App\Entity\WebOrder;
use App\Service\Aggregator\UpdateDeliveryParent;

abstract class MiraklUpdateDeliveryParent extends UpdateDeliveryParent
{
    protected function getMiraklApi(): MiraklApiParent
    {
        return $this->getApi();
    }
}
