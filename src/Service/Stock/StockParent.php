<?php

namespace App\Service\Stock;

use App\Entity\ProductCorrelation;
use App\Entity\WebOrder;
use App\Helper\BusinessCentral\Connector\BusinessCentralConnector;
use App\Service\BusinessCentral\BusinessCentralAggregator;
use App\Service\MailService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;


abstract class StockParent
{


    protected $logger;

    protected $manager;

    protected $mailer;

    protected $businessCentralAggregator;


    public function __construct(ManagerRegistry $manager, LoggerInterface $logger, MailService $mailer, BusinessCentralAggregator $businessCentralAggregator)
    {
        $this->logger = $logger;
        $this->manager = $manager->getManager();
        $this->mailer = $mailer;
        $this->businessCentralAggregator = $businessCentralAggregator;
    }


    abstract public function sendStocks();

    abstract public function getChannel();

    protected function getStocksProductWarehouse(array $skus, $depot = WebOrder::DEPOT_CENTRAL)
    {
    }

    protected function getStockProductWarehouse($sku, $depot = WebOrder::DEPOT_CENTRAL): int
    {
        $skuFinal = $this->getProductCorrelationSku($sku);
        $product = $this->getBusinessCentralConnector(BusinessCentralConnector::KIT_PERSONALIZACION_SPORT)->getItemByNumber($skuFinal);
        if (!$product) {
            return 0;
        } else {
            return  $product['inventory'];
        }
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
}
