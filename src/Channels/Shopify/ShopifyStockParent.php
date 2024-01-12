<?php

namespace App\Channels\Shopify;

use App\Channels\Shopify\ShopifyApiParent;
use App\Entity\WebOrder;
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
            $sku = $this->getCorrelatedSku($inventoLeveli['sku']);
            if (!$this->productStockFinder->isBundle($sku)) {
                if ($this->checkIfProductSellableOnChannel($sku)) {
                    $stockLevel = $this->getStockProductWarehouse($sku, $this->getDefaultWarehouse());
                    $this->logger->info('Update modified ' . $sku  . ' >>> ' . $stockLevel);
                } else {
                    $stockLevel = 0;
                    $this->logger->info('SHould be desactivated ' . $sku);
                }

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


    protected function getDefaultWarehouse()
    {
        return WebOrder::DEPOT_LAROCA;
    }




    public function checkStocks(): array
    {
        $errors=[];
        $inventoLevelies = $this->getShopifyApi()->getAllInventoryLevelsFromProduct();
        foreach ($inventoLevelies as $inventoLeveli) {
            $sku = $this->getCorrelatedSku($inventoLeveli['sku']);
            if ($this->productStockFinder->isBundle($sku)) {
                $this->logger->info('Bundle ' . $sku  . ' no check');
            } else {
                $this->logger->info('Check ' . $sku);
                if (!$this->isSkuExists($sku)) {
                    $errors[] = 'Sku '.$sku. ' for '.$inventoLeveli['product_title'].' do not exist in BC and no sku mappings have been done also.';
                }
            }
        }
        return $errors;
    }


    public function getCorrelatedSku($sku)
    {
        return $sku;
    }

}
