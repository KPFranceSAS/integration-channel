<?php

namespace App\Channels\Shopify;

use App\Channels\Shopify\ShopifyApiParent;
use App\Service\Aggregator\StockParent;

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
            if ($this->isNotBundle($sku)) {
                $stockLevel = $this->getStockProductWarehouse($sku);
                $this->logger->info('Update modified ' . $sku  . ' >>> ' . $stockLevel);
                $this->getShopifyApi()->setInventoryLevel(
                    $mainLocation['id'],
                    $inventoLeveli['inventory_item_id'],
                    $stockLevel
                );
            } else {
                $this->logger->info('Bundle ' . $sku  . ' no modification');
            }
        }
    }



    public function checkStocks(): array
    {
        $errors=[];
        $inventoLevelies = $this->getShopifyApi()->getAllInventoryLevelsFromProduct();
        foreach ($inventoLevelies as $inventoLeveli) {
            $sku = $inventoLeveli['sku'];
            if ($this->isNotBundle($sku)) {
                if(!$this->isSkuExists($sku)){
                    $errors[] = 'Sku '.$sku. ' do not exist in BC and no sku mappings have been done also.';
                }
            } else {
                $this->logger->info('Bundle ' . $sku  . ' no check');
            }
        }
        return $errors;
    }
}
