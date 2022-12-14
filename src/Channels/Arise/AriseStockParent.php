<?php

namespace App\Channels\Arise;

use App\Channels\Arise\AriseApiParent;
use App\Entity\WebOrder;
use App\Service\Aggregator\StockParent;

abstract class AriseStockParent extends StockParent
{
    protected function getAriseApi(): AriseApiParent
    {
        return $this->getApi();
    }



    /**
     * process all invocies directory
     *
     * @return void
     */
    public function sendStocks()
    {
        $products = $this->getAriseApi()->getAllProducts();
        foreach ($products as $product) {
            $this->sendStock($product);
        }
    }



    public function sendStock($product)
    {
        $name = (property_exists($product, 'attributes') && property_exists($product->attributes, 'name')) ? $product->attributes->name : null;
        $this->logger->info('Send stock for ' . $name . ' / Id ' . $product->item_id);
        $stockTocHeck = WebOrder::DEPOT_LAROCA;
        foreach ($product->skus as $sku) {
            $stockBC = $this->getStockProductWarehouse($sku->SellerSku, $stockTocHeck);
            $this->logger->info('Sku ' . $sku->SellerSku   . ' / stock BC ' . $stockBC . ' units in ' . $stockTocHeck);
            if ($this->checkIfProductSellableOnChannel($sku->SellerSku)) {
                $this->getAriseApi()->updateStockLevel($product->item_id, $sku->SkuId, $sku->SellerSku, $stockBC);
            } else {
                $this->logger->info('Sku ' . $sku->SellerSku   . ' is disabled ');
                $this->getAriseApi()->updateStockLevel($product->item_id, $sku->SkuId, $sku->SellerSku, 0);
            }
        }
        $this->logger->info('---------------');
    }



   



    public function checkStocks(): array
    {
        $errors=[];
        $products = $this->getAriseApi()->getAllProducts();
        foreach ($products as $product) {
            $name = (property_exists($product, 'attributes') && property_exists($product->attributes, 'name')) ? $product->attributes->name : null;
            $this->logger->info('Check stock for ' . $name . ' / Id ' . $product->item_id);
            foreach ($product->skus as $sku) {
                $this->logger->info('Sku ' . $sku->SellerSku);
                if (!$this->isSkuExists($sku->SellerSku)) {
                    $errors[] = 'Sku '.$sku->SellerSku. ' do not exist in BC and no sku mappings have been done also.';
                }
            }
        }
        return $errors;
    }
}
