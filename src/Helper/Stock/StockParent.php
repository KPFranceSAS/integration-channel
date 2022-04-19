<?php

namespace App\Helper\Stock;

use App\Entity\ProductCorrelation;
use App\Entity\WebOrder;
use App\Helper\Api\ApiAggregator;
use App\Helper\BusinessCentral\Connector\BusinessCentralConnector;
use App\Service\BusinessCentral\BusinessCentralAggregator;
use App\Service\MailService;
use Doctrine\Persistence\ManagerRegistry;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;


abstract class StockParent
{


    protected $logger;

    protected $manager;

    protected $mailer;

    protected $apiAggregator;

    protected $businessCentralAggregator;

    protected $awsStorage;

    protected $stockLevels;


    public function __construct(FilesystemOperator $awsStorage, ManagerRegistry $manager, LoggerInterface $logger, MailService $mailer, BusinessCentralAggregator $businessCentralAggregator,  ApiAggregator $apiAggregator)
    {
        $this->logger = $logger;
        $this->manager = $manager->getManager();
        $this->mailer = $mailer;
        $this->businessCentralAggregator = $businessCentralAggregator;
        $this->awsStorage = $awsStorage;
        $this->apiAggregator = $apiAggregator;
    }


    public function send()
    {
        try {
            $this->sendStocks();
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->mailer->sendEmail('[Send stock Integration ' . $this->getChannel() . '] Error', $e->getMessage());
        }
    }


    abstract public function sendStocks();

    abstract public function getChannel();

    public function getApi()
    {
        return $this->apiAggregator->getApi($this->getChannel());
    }

    protected function getStocksProductWarehouse(array $skus, $depot = WebOrder::DEPOT_CENTRAL)
    {
    }

    protected function getStockProductWarehouse($sku, $depot = WebOrder::DEPOT_CENTRAL): int
    {
        if (!$this->stockLevels) {
            $this->initializeStockLevels();
        }
        $skuFinal = $this->getProductCorrelationSku($sku);
        $key = $skuFinal . '_' . $depot;
        if (array_key_exists($key, $this->stockLevels)) {
            $stock = $this->stockLevels[$key];
            $this->logger->info('Stock available ' . $skuFinal . ' in ' . $depot . ' >>> ' . $stock);
            if ($depot == WebOrder::DEPOT_CENTRAL) {
                if ($stock >= 5) {
                    return round(0.7 * $stock, 0, PHP_ROUND_HALF_DOWN);
                }
            } elseif ($depot == WebOrder::DEPOT_MADRID) {
                return $stock;
            }
        } else {
            $this->logger->error('Not found ' . $skuFinal . ' in ' . $depot);
        }
        return 0;
    }

    public function getBusinessCentralConnector($companyName): BusinessCentralConnector
    {
        return $this->businessCentralAggregator->getBusinessCentralConnector($companyName);
    }


    protected function getProductCorrelationSku(string $sku): string
    {
        $skuSanitized = strtoupper($sku);
        $productCorrelation = $this->manager->getRepository(ProductCorrelation::class)->findOneBy(['skuUsed' => $skuSanitized]);
        return $productCorrelation ? $productCorrelation->getSkuErp() : $skuSanitized;
    }



    public function initializeStockLevels()
    {
        $this->logger->info('Get the file stock/stocks.csv');
        $this->stockLevels = [];
        $contentFile = $this->awsStorage->readStream('stock/StockMarketplaces.csv');
        $toRemove = fgetcsv($contentFile, null, ';');
        $header = fgetcsv($contentFile, null, ';');
        while (($values = fgetcsv($contentFile, null, ';')) !== false) {
            if (count($values) == count($header)) {
                $stock = array_combine($header, $values);
                $key = $stock['SKU'] . '_' . $stock['LocationCode'];
                $this->stockLevels[$key] = (int)$stock['AvailableQty'];
            }
        }
        if (count($this->stockLevels) == 0) {
            throw new \Exception('Error of mapping for stock files published ' . json_encode($header));
        }


        if (count($this->stockLevels) < 5000) {
            throw new \Exception('Error with number lines of stock files published ' . count($this->stockLevels));
        }

        $this->logger->info('Nb of lines :' . count($this->stockLevels));
        return $this->stockLevels;
    }
}
