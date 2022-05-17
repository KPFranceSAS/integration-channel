<?php

namespace App\Service\OwletCare;

use App\Entity\WebOrder;
use App\Helper\Stock\ShopifyStockParent;

class OwletCareStock extends ShopifyStockParent
{
    public function getChannel()
    {
        return WebOrder::CHANNEL_OWLETCARE;
    }
}
