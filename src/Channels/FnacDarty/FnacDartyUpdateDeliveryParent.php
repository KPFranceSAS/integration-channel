<?php

namespace App\Channels\FnacDarty;

use App\Channels\FnacDarty\FnacDartyApi;
use App\Service\Aggregator\UpdateDeliveryParent;

abstract class FnacDartyUpdateDeliveryParent extends UpdateDeliveryParent
{
    protected function getFnacDartyApi(): FnacDartyApi
    {
        return $this->getApi();
    }
}
