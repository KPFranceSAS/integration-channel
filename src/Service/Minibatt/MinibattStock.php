<?php

namespace App\Service\Minibatt;

use App\Entity\WebOrder;
use App\Helper\Stock\ShopifyStockParent;

class MinibattStock extends ShopifyStockParent
{
    public function getChannel()
    {
        return WebOrder::CHANNEL_MINIBATT;
    }
}
