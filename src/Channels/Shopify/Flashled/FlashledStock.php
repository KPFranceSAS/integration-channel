<?php

namespace App\Channels\Shopify\Flashled;

use App\Entity\WebOrder;
use App\Channels\Shopify\ShopifyStockParent;

class FlashledStock extends ShopifyStockParent
{
    public function getChannel()
    {
        return WebOrder::CHANNEL_FLASHLED;
    }




    public function getStockProductWarehouse($sku, $depot = WebOrder::DEPOT_LAROCA): int
    {
        if ($sku == 'FL-FLASHLED-SOS') {
            return 999; // case preorder
        } else {
            return parent::getStockProductWarehouse($sku, $depot);
        }
    }
}
