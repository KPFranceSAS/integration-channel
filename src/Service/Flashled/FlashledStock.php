<?php

namespace App\Service\Flashled;

use App\Entity\WebOrder;
use App\Helper\Stock\ShopifyStockParent;

class FlashledStock extends ShopifyStockParent
{
    public function getChannel()
    {
        return WebOrder::CHANNEL_FLASHLED;
    }
}
