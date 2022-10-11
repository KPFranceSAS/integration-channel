<?php

namespace App\Channels\Shopify\Flashled;

use App\Channels\Shopify\ShopifyStockParent;
use App\Entity\IntegrationChannel;
use App\Entity\WebOrder;

class FlashledStock extends ShopifyStockParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_FLASHLED;
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
