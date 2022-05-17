<?php

namespace App\Helper\Stock;

use App\Helper\Api\ShopifyApiParent;
use App\Helper\Stock\StockParent;

abstract class ShopifyStockParent extends StockParent
{

    protected function getShopifyApi(): ShopifyApiParent
    {
        return $this->getApi();
    }



    public function sendStocks()
    {
        $mainLocation = $this->getShopifyApi()->getMainLocation();
        $inventoLevelies = $this->getShopifyApi()->getAllInventoryLevelsFromProduct();
        foreach ($inventoLevelies as $inventoLeveli) {
            $sku = $inventoLeveli['sku'];
            $stockLevel = $this->getStockProductWarehouse($sku);
            $this->getShopifyApi()->setInventoryLevel($mainLocation['id'], $inventoLeveli['inventory_item_id'], $stockLevel);
        }
    }
}
