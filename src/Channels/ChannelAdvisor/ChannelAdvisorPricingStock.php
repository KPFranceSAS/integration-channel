<?php

namespace App\Channels\ChannelAdvisor;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\BusinessCentral\ProductStockFinder;
use App\BusinessCentral\ProductTaxFinder;
use App\Entity\IntegrationChannel;
use App\Entity\Product;
use App\Helper\MailService;
use App\Service\Aggregator\ApiAggregator;
use App\Service\Aggregator\PriceParent;
use App\Service\Aggregator\PriceStockParent;
use Doctrine\Persistence\ManagerRegistry;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use stdClass;

class ChannelAdvisorPricingStock extends PriceStockParent
{
    protected $logger;
    protected $defaultStorage;
    protected $channelAdvisorStorage;

    public function __construct(
        ManagerRegistry $manager,
        LoggerInterface $logger,
        MailService $mailer,
        BusinessCentralAggregator $businessCentralAggregator,
        ApiAggregator $apiAggregator,
        ProductStockFinder $productStockFinder,
        ProductTaxFinder $productTaxFinder,
        FilesystemOperator $defaultStorage,
        FilesystemOperator $channelAdvisorStorage
    ) {
        $this->defaultStorage = $defaultStorage;
        $this->channelAdvisorStorage = $channelAdvisorStorage;
        parent::__construct($manager, $logger, $mailer, $businessCentralAggregator, $apiAggregator, $productStockFinder, $productTaxFinder);
    }



    public function getChannel(): string
    {
        return  IntegrationChannel::CHANNEL_CHANNELADVISOR;
    }





    public function sendStocksPrices(array $products, array $saleChannels)
    {
        $header = ['sku', 'stock_laroca'];
        foreach ($saleChannels as $saleChannel) {
            $code = $saleChannel->getCode().'-';
            array_push($header, $code.'enabled', $code.'price', $code.'promoprice');
        }
        $datasToExport=[implode(';', $header)];
        $this->logger->info("start export ".count($products)." products on ".count($saleChannels)." sale channels");
        foreach ($products as $product) {
            $productArray = $this->addProduct($product, $header, $saleChannels);
            $datasToExport[]=implode(';', array_values($productArray));
        }

        $dataArray = implode("\r\n", $datasToExport);
        $filename = 'pricing_'.date('Ymd_His').'.PRICE.csv';
        $this->logger->info("start export pricing locally");
        $this->defaultStorage->write('pricings/'.$filename, $dataArray);
        $this->logger->info("start export pricing on channeladvisor");
        
        $this->channelAdvisorStorage->write('/accounts/12009934/Inventory/Transform/'.$filename, $dataArray);
        $this->channelAdvisorStorage->write('/accounts/12010023/Inventory/Transform/'.$filename, $dataArray);
        $this->channelAdvisorStorage->write('/accounts/12010024/Inventory/Transform/'.$filename, $dataArray);
        $this->channelAdvisorStorage->write('/accounts/12010025/Inventory/Transform/'.$filename, $dataArray);
        $this->channelAdvisorStorage->write('/accounts/12010026/Inventory/Transform/'.$filename, $dataArray);
        $this->channelAdvisorStorage->write('/accounts/12044694/Inventory/Transform/'.$filename, $dataArray);
        $this->channelAdvisorStorage->write('/accounts/12044693/Inventory/Transform/'.$filename, $dataArray);
    }


    private function addProduct(Product $product, array $header, array $saleChannels): array
    {
        $productArray = array_fill_keys($header, null);
        $productArray['sku'] = $product->getSku();
        $productArray['stock_laroca'] = $this->productStockFinder->getFinalStockProductWarehouse($product->getSku());
        
        foreach ($saleChannels as $saleChannel) {
            $code = $saleChannel->getCode().'-';
            $productMarketplace = $product->getProductSaleChannelByCode($saleChannel->getCode());
          
            if ($productMarketplace->getEnabled()) {
                $productArray[$code.'enabled']= 1 ;
                $productArray[$code.'price']= $productMarketplace->getPrice() ;
                $promotion = $productMarketplace->getBestPromotionForNow();
                if ($promotion) {
                    $productArray[$code.'promoprice']= $promotion->getPromotionPrice() ;
                }
            } else {
                $productArray[$code.'enabled']= 0;
            }
        }

        $businessCentralConnector = $this->businessCentralAggregator->getBusinessCentralConnector(BusinessCentralConnector::KP_FRANCE);

        $itemBc = $businessCentralConnector->getItemByNumber($product->getSku());
        $productArray['ecotax'] =  $this->productTaxFinder->getEcoTaxForItem(
            $itemBc,
            BusinessCentralConnector::KP_FRANCE,
            'FR'
        );


        return $productArray;
    }
}
