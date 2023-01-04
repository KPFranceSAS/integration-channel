<?php

namespace App\Channels\Arise;

use App\Channels\Arise\AriseApiParent;
use App\Entity\WebOrder;
use App\Service\Aggregator\UpdateDeliveryParent;

abstract class AriseUpdateDeliveryParent extends UpdateDeliveryParent
{
    protected function getAriseApi(): AriseApiParent
    {
        return $this->getApi();
    }


    protected function postUpdateStatusDelivery(WebOrder $order)
    {
        if($order->isFulfiledByDhl()){
            $markOk =  $this->getAriseApi()->markOrderAsDelivered($order->getExternalNumber());
            if ($markOk) {
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }
}
