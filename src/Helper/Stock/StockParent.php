<?php

namespace App\Helper\Stock;

use App\Entity\ProductCorrelation;
use App\Entity\WebOrder;
use App\Helper\BusinessCentral\Connector\BusinessCentralConnector;
use App\Service\Aggregator\ApiAggregator;
use App\Service\BusinessCentral\BusinessCentralAggregator;
use App\Service\BusinessCentral\ProductStockFinder;
use App\Service\MailService;
use Doctrine\Persistence\ManagerRegistry;
use function Symfony\Component\String\u;
use Psr\Log\LoggerInterface;

abstract class StockParent
{
    protected $logger;

    protected $manager;

    protected $mailer;

    protected $apiAggregator;

    protected $businessCentralAggregator;

    protected $productStockFinder;

    public function __construct(ManagerRegistry $manager, LoggerInterface $logger, MailService $mailer, BusinessCentralAggregator $businessCentralAggregator, ApiAggregator $apiAggregator, ProductStockFinder $productStockFinder)
    {
        $this->logger = $logger;
        $this->manager = $manager->getManager();
        $this->mailer = $mailer;
        $this->businessCentralAggregator = $businessCentralAggregator;
        $this->productStockFinder = $productStockFinder;
        $this->apiAggregator = $apiAggregator;
    }

    abstract public function sendStocks();

    abstract public function getChannel();


    public function send()
    {
        try {
            $this->sendStocks();
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->mailer->sendEmailChannel($this->getChannel(), 'Send stock Integration - Error', $e->getMessage());
        }
    }

    public function getApi()
    {
        return $this->apiAggregator->getApi($this->getChannel());
    }

    
    public function getStockProductWarehouse($sku, $depot = WebOrder::DEPOT_LAROCA): int
    {
        $skuFinal = $this->getProductCorrelationSku($sku);
        $stock = $this->productStockFinder->getRealStockProductWarehouse($skuFinal, $depot);
        if ($stock > 0) {
            if ($depot == WebOrder::DEPOT_LAROCA) {
                if ($stock >= 5) {
                    return round(0.7 * $stock, 0, PHP_ROUND_HALF_DOWN);
                }
            } elseif ($depot == WebOrder::DEPOT_MADRID) {
                return $stock;
            }
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
        $productCorrelation = $this->manager
                                ->getRepository(ProductCorrelation::class)
                                ->findOneBy(['skuUsed' => $skuSanitized]);
        return $productCorrelation ? $productCorrelation->getSkuErpBc() : $skuSanitized;
    }

    
    protected function isNotBundle(string $sku): bool
    {
        return u($sku)->containsAny('-PCK-') ? false : true;
    }
}
