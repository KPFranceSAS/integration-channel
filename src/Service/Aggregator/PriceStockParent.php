<?php

namespace App\Service\Aggregator;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\BusinessCentral\ProductStockFinder;
use App\Entity\IntegrationChannel;
use App\Entity\Product;
use App\Entity\SaleChannel;
use App\Entity\WebOrder;
use App\Helper\MailService;
use App\Service\Aggregator\ApiAggregator;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

abstract class PriceStockParent
{
    protected $logger;

    protected $manager;

    protected $mailer;

    protected $apiAggregator;

    protected $businessCentralAggregator;

    protected $productStockFinder;


    public function __construct(
        ManagerRegistry $manager,
        LoggerInterface $logger,
        MailService $mailer,
        BusinessCentralAggregator $businessCentralAggregator,
        ApiAggregator $apiAggregator,
        ProductStockFinder $productStockFinder
    ) {
        $this->logger = $logger;
        $this->manager = $manager->getManager();
        $this->mailer = $mailer;
        $this->apiAggregator = $apiAggregator;
        $this->businessCentralAggregator = $businessCentralAggregator;
        $this->productStockFinder = $productStockFinder;
    }

    abstract public function sendStocksPrices(array $products, array $saleChannels);

    abstract public function getChannel(): string;


    public function send()
    {
        try {
            $integrationChannel = $this->manager->getRepository(IntegrationChannel::class)->findBy([
                'code' => $this->getChannel()
            ]);

            $saleChannels = $this->manager->getRepository(SaleChannel::class)->findBy([
                'integrationChannel' => $integrationChannel
            ]);

            $products = $this->manager->getRepository(Product::class)->findAll();
            $productFiltered = $this->getFilteredProducts($products, $saleChannels);
            $this->sendStocksPrices($productFiltered, $saleChannels);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->mailer->sendEmailChannel($this->getChannel(), 'Update prices and stock Error class '. get_class($this), $e->getMessage());
        }
    }

    
    protected function isEnabledProducts(Product $product, $saleChannels) : bool
    {
        foreach ($saleChannels as $saleChannel) {
            $productMarketplace = $product->getProductSaleChannelByCode($saleChannel->getCode());
            if ($productMarketplace->getEnabled()) {
                return true;
            }
        }
        return false;
    }


    public function getStockProductWarehouse($sku, $depot = WebOrder::DEPOT_LAROCA): int
    {
        return $this->productStockFinder->getFinalStockProductWarehouse($sku, $depot);
    }

    protected function getFilteredProducts(array $products, $saleChannels): array
    {
        $productsFiltererd=[];
        foreach ($products as $product) {
            if ($this->isEnabledProducts($product, $saleChannels)) {
                $productsFiltererd[] = $product;
            }
        }
        return $productsFiltererd;
    }



    public function getApi()
    {
        return $this->apiAggregator->getApi($this->getChannel());
    }
}
