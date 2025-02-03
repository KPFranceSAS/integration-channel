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


    protected $manager;

    protected $projectDir;

    public function __construct(
        ManagerRegistry $manager,
        protected LoggerInterface $logger,
        protected MailService $mailer,
        protected BusinessCentralAggregator $businessCentralAggregator,
        protected ApiAggregator $apiAggregator,
        protected ProductStockFinder $productStockFinder,
        protected ProductTaxFinder $productTaxFinder,
        $projectDir
    ) {
        $this->projectDir =  $projectDir.'/public/catalogue/'.$this->getLowerChannel().'/';
        $this->manager = $manager->getManager();
    }

    abstract public function sendStocksPrices(array $products, array $saleChannels);

    abstract public function getChannel(): string;


    protected function getLowerChannel()
    {
        return strtolower($this->getChannel());
    }



    protected function getSaleChannels(){
        $integrationChannel = $this->manager->getRepository(IntegrationChannel::class)->findBy([
            'code' => $this->getChannel()
        ]);

        $saleChannels = $this->manager->getRepository(SaleChannel::class)->findBy([
            'integrationChannel' => $integrationChannel
        ]);
        $this->logger->info('Has '.count($saleChannels).' sale channels enabled');

        return $saleChannels;
    }


    public function send()
    {
        try {
            $productFiltered = $this->getFilteredProducts();
            $this->logger->info('Has '.count($productFiltered).' products enabled');
            if(count($productFiltered)>0) {
               return  $this->sendStocksPrices($productFiltered, $this->getSaleChannels());
            } else {
                return [];
            }

            
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->mailer->sendEmailChannel($this->getChannel(), 'Update prices and stock Error class '. static::class, $e->getMessage());
        }
    }

    

    public function getStockProductWarehouse($sku, $depot = WebOrder::DEPOT_MONTMELO): int
    {
        return $this->productStockFinder->getFinalStockProductWarehouse($sku, $depot);
    }

    protected function getFilteredProducts(): array
    {
        $saleChannels = $this->getSaleChannels();
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
