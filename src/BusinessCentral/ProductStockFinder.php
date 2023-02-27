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


    public function getRealStocksProductWarehouse(array $skus, $depot = WebOrder::DEPOT_LAROCA): array
    {
        $skuStocks = [];
        foreach ($skus as $sku) {
            $skuStocks[$sku] = $this->getRealStockProductWarehouse($sku, $depot);
        }
        return $skuStocks;
    }

    public function getRealStockProductWarehouse($sku, $depot = WebOrder::DEPOT_LAROCA): int
    {
        if (array_key_exists($sku, $this->stockLevels)) {
            $stockAvailbility = $this->stockLevels[$sku];
            $this->logger->info('Stock available ' . $stockAvailbility['no'] . ' in ' . $depot . ' >>> ' . $stockAvailbility['quantityAvailable'.$depot]);
            return  $stockAvailbility['quantityAvailable'.$depot];
        } else {
            $this->logger->info('Retrieve data from BC ' . $sku . ' in ' . $depot);
            $skuAvalibility = $this->businessCentralAggregator->getBusinessCentralConnector(BusinessCentralConnector::KIT_PERSONALIZACION_SPORT)->getStockAvailabilityPerProduct($sku);
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
}
