<?php

namespace App\Service\Aggregator;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\BusinessCentral\ProductStockFinder;
use App\BusinessCentral\ProductTaxFinder;
use App\Entity\IntegrationChannel;
use App\Entity\Product;
use App\Entity\ProductSaleChannel;
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

    protected $productTaxFinder;

    public function __construct(
        ManagerRegistry $manager,
        LoggerInterface $logger,
        MailService $mailer,
        BusinessCentralAggregator $businessCentralAggregator,
        ApiAggregator $apiAggregator,
        ProductStockFinder $productStockFinder,
        ProductTaxFinder $productTaxFinder
    ) {
        $this->logger = $logger;
        $this->manager = $manager->getManager();
        $this->mailer = $mailer;
        $this->apiAggregator = $apiAggregator;
        $this->businessCentralAggregator = $businessCentralAggregator;
        $this->productStockFinder = $productStockFinder;
        $this->productTaxFinder = $productTaxFinder;
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
            $this->logger->info('Has '.count($saleChannels).' sale channels enabled');

            $productFiltered = $this->getFilteredProducts($saleChannels);
            $this->logger->info('Has '.count($productFiltered).' products enabled');
            if(count($productFiltered)>0) {
                $this->sendStocksPrices($productFiltered, $saleChannels);
            }
            
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->mailer->sendEmailChannel($this->getChannel(), 'Update prices and stock Error class '. get_class($this), $e->getMessage());
        }
    }

    

    public function getStockProductWarehouse($sku, $depot = WebOrder::DEPOT_LAROCA): int
    {
        return $this->productStockFinder->getFinalStockProductWarehouse($sku, $depot);
    }

    protected function getFilteredProducts($saleChannels): array
    {
        $productsFiltererd=[];
        foreach($saleChannels as $saleChannel) {
            $productMarketplaces = $this->manager->getRepository(ProductSaleChannel::class)->findBy(
                [
                    'saleChannel'=> $saleChannel,
                    'enabled' => true
                ]
            );

            foreach ($productMarketplaces as $productMarketplace) {
                $product = $productMarketplace->getProduct();
                $productsFiltererd[$product->getSku()] = $product;
            }
        }

        return array_values($productsFiltererd);
    }



    public function getApi()
    {
        return $this->apiAggregator->getApi($this->getChannel());
    }
}
