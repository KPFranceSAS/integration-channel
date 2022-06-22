<?php

namespace App\Service\BusinessCentral;

use App\Entity\WebOrder;
use App\Service\MailService;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;

class ProductStockFinder
{
    protected $logger;

    protected $manager;

    protected $awsStorage;

    protected $mailService;

    protected $stockLevels;


    public function __construct(FilesystemOperator $awsStorage, ManagerRegistry $manager, LoggerInterface $logger, MailService $mailService)
    {
        $this->logger = $logger;
        $this->manager = $manager->getManager();
        $this->awsStorage = $awsStorage;
        $this->mailService = $mailService;
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
        if (!$this->stockLevels) {
            $this->initializeStockLevels();
        }
        $key = $sku . '_' . $depot;
        if (array_key_exists($key, $this->stockLevels)) {
            $stock = $this->stockLevels[$key];
            $this->logger->info('Stock available ' . $sku . ' in ' . $depot . ' >>> ' . $stock);
            return $stock;
        } else {
            $this->logger->error('Not found ' . $sku . ' in ' . $depot);
        }
        return 0;
    }


    public function initializeStockLevels()
    {
        $this->logger->info('Get the file stock/StockMarketplaces.csv');
        $this->stockLevels = [];
        $contentFile = $this->awsStorage->readStream('stock/StockMarketplaces.csv');
        $toRemove = fgetcsv($contentFile, null, ';');
        $header = fgetcsv($contentFile, null, ';');


        $lastModifedTime = $this->awsStorage->lastModified('stock/StockMarketplaces.csv');
        $differenceCreationMinutes = round((time() - $lastModifedTime) / 60, 0);

        $this->logger->info('Updated : ' . $differenceCreationMinutes . ' minutes');

        if ($differenceCreationMinutes > 180) {
            $this->mailService->sendEmail('Stock Unpublished', 'Update of the stock files published has not been done for  ' . $differenceCreationMinutes . ' minutes');
            //throw new Exception('Update of the stock files published has not been done for  ' . $differenceCreationMinutes . ' minutes');
        }

        $warehouseFiles = [
            WebOrder::DEPOT_CENTRAL => WebOrder::DEPOT_CENTRAL,
            WebOrder::DEPOT_FBA_AMAZON => WebOrder::DEPOT_FBA_AMAZON,
            WebOrder::DEPOT_LAROCA => WebOrder::DEPOT_LAROCA,
            WebOrder::DEPOT_MADRID => WebOrder::DEPOT_MADRID,
        ];


        while (($values = fgetcsv($contentFile, null, ';')) !== false) {
            if (count($values) == count($header)) {
                $stock = array_combine($header, $values);
                $nameWarehouse = $stock['LocationCode'];
                $key = $stock['SKU'] . '_' . $nameWarehouse;
                $this->stockLevels[$key] = (int)$stock['AvailableQty'];
                if (array_key_exists($nameWarehouse, $warehouseFiles)) {
                    unset($warehouseFiles[$nameWarehouse]);
                }
            }
        }

        $this->logger->info('Nb of lines in the files : ' . count($this->stockLevels));

        if (count($this->stockLevels) == 0) {
            throw new Exception('Error of mapping for stock files published ' . json_encode($header));
        }

        if (count($this->stockLevels) < 5000) {
            throw new Exception('Error with number lines of stock files published ' . count($this->stockLevels));
        }

        if (count($warehouseFiles) > 0) {
            throw new Exception('Error with number of warehouse in stock files published. Missing the warehouses ' . implode(', ', $warehouseFiles));
        }


        return $this->stockLevels;
    }
}
