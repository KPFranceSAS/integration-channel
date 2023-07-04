<?php

namespace App\BusinessCentral;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Entity\WebOrder;
use Psr\Log\LoggerInterface;

class ProductStockFinder
{
    protected $logger;

    protected $businessCentralAggregator;

    protected $stockLevels;


    public function __construct(LoggerInterface $logger, BusinessCentralAggregator $businessCentralAggregator)
    {
        $this->logger = $logger;
        $this->businessCentralAggregator = $businessCentralAggregator;
        $this->stockLevels = [];
    }



    

    protected function getStockAvailability($sku, $depot = WebOrder::DEPOT_LAROCA): int
    {
        if (array_key_exists($sku, $this->stockLevels)) {
            $stockAvailbility = $this->stockLevels[$sku];
            $this->logger->info('Stock available ' . $stockAvailbility['no'] . ' in ' . $depot . ' >>> ' . $stockAvailbility['quantityAvailable'.$depot]);
            return  $stockAvailbility['quantityAvailable'.$depot];
        } else {
            $this->logger->info('Retrieve data from BC ' . $sku . ' in ' . $depot);
            $skuAvalibility =  $this->getConnector()->getStockAvailabilityPerProduct($sku);
            if ($skuAvalibility) {
                $this->stockLevels[$sku] = $skuAvalibility;
                $this->logger->info('Stock available ' . $skuAvalibility['no'] . ' in ' . $depot . ' >>> ' . $skuAvalibility['quantityAvailable'.$depot]);
                return $skuAvalibility['quantityAvailable'.$depot];
            } else {
                $this->logger->error('Not found ' . $sku . ' in ' . $depot);
            }
        }
        return 0;
    }

    /**
     * Returns the real level of stock of product or bundle
     */
    public function getRealStockProductWarehouse($sku, $depot = WebOrder::DEPOT_LAROCA): int
    {
        return $this->getFinalStockProductWarehouse($sku, $depot, false);
    }

    

    /**
     * Returns the ponderated level of stock of product or bundle
     */
    public function getFinalStockProductWarehouse($sku, $depot = WebOrder::DEPOT_LAROCA, $ponderated=true): int
    {
        $this->logger->info('------ Check stock '.$sku.' in depot '.$depot.' ------ ');

        if(in_array($sku, ['ANK-PCK-7', 'ANK-PCK-8', 'ANK-PCK-9','ANK-PCK-10'])) {
            return 10;
        }




        if ($this->isBundle($sku)) {
            $this->logger->info('Sku '.$sku.' is bundle');
            
            $stock =  $this->getFinalStockBundleWarehouse($sku, $depot, $ponderated);
        } else {
            $stock = $this->getFinalStockComponentWarehouse($sku, $depot, $ponderated);
        }
        $this->logger->info('Stock '.$sku.' in depot '.$depot.' >>> '.$stock);

        return $stock;
    }

    /**
     * Returns the level of stock of simple product
     */
    protected function getFinalStockComponentWarehouse($sku, $depot = WebOrder::DEPOT_LAROCA, $ponderated=true): int
    {
        $stock = $this->getStockAvailability($sku, $depot);
        $this->logger->info($ponderated ? 'Stock level ponterated' : 'Stock level non-ponderated');
        if ($ponderated===false) {
            return $stock;
        }

        if ($stock >= 150) {
            return round(0.9 * $stock, 0, PHP_ROUND_HALF_DOWN);
        } elseif ($stock >= 100) {
            return round(0.8 * $stock, 0, PHP_ROUND_HALF_DOWN);
        } elseif ($stock >= 50) {
            return round(0.75 * $stock, 0, PHP_ROUND_HALF_DOWN);
        } elseif ($stock >= 5) {
            return round(0.7 * $stock, 0, PHP_ROUND_HALF_DOWN);
        }
        return 0;
    }



    /**
     * Returns the level of stock of bundle product
     */
    protected function getFinalStockBundleWarehouse($sku, $depot, $ponderated): int
    {
        $components = $this->getConnector()->getComponentsBundle($sku);
        
        $availableStock = PHP_INT_MAX;
        foreach ($components as $component) {
            if ($component['Quantity'] == 0) {
                $availableStock = 0;
                break;
            }
            $stock = $this->getFinalStockComponentWarehouse($component['ComponentSKU'], $depot, $ponderated);
            $componentStock = floor($stock / $component['Quantity']);
            $this->logger->info("Component ".$component['ComponentSKU']." capacity in ".$componentStock);

            if ($componentStock < $availableStock) {
                $availableStock = $componentStock;
            }
        }

        $this->logger->info("Avaibility bundle ".$availableStock);

        return $availableStock;
    }



    /**
     * Check if it is a bundle
     */
    public function isBundle($sku): bool
    {
        $item =  $this->getConnector()->getItemByNumber($sku);

        if ($item['AssemblyBOM']==false) {
            return false;
        }

        if ($item['AssemblyBOM']==true && in_array($item['AssemblyPolicy'], ["Assemble-to-Stock", "Ensamblar para stock"])) {
            return false;
        }
        return true;
    }


    protected function getConnector()
    {
        return $this->businessCentralAggregator->getBusinessCentralConnector(BusinessCentralConnector::KIT_PERSONALIZACION_SPORT);
    }
}
