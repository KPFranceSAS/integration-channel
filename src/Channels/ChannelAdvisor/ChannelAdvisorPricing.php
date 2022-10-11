<?php

namespace App\Channels\ChannelAdvisor;

use App\Entity\IntegrationChannel;
use App\Entity\Product;
use App\Entity\SaleChannel;
use Doctrine\Persistence\ManagerRegistry;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use stdClass;

class ChannelAdvisorPricing
{
    protected $logger;
    protected $defaultStorage;
    protected $channelAdvisorStorage;
    protected $managerRegistry;


    public function __construct(LoggerInterface $logger, FilesystemOperator $defaultStorage, FilesystemOperator $channelAdvisorStorage, ManagerRegistry $managerRegistry)
    {
        $this->logger = $logger;
        $this->defaultStorage = $defaultStorage;
        $this->channelAdvisorStorage = $channelAdvisorStorage;
        $this->managerRegistry = $managerRegistry->getManager();
    }


    public function exportPricings()
    {
        $saleChannels = $this->managerRegistry->getRepository(SaleChannel::class)->findBy([
            'channel' => IntegrationChannel::CHANNEL_CHANNELADVISOR
        ]);

        /**
         * @var array[\App\Entity\Product]
         */
        $products = $this->managerRegistry->getRepository(Product::class)->findAll();
        

        $header = ['sku'];
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
        $this->channelAdvisorStorage->write('/accounts/12044693/Inventory/Transform/'.$filename, $dataArray);
    }


    private function addProduct(Product $product, array $header, array $saleChannels): array
    {
        $productArray = array_fill_keys($header, null);
        $productArray['sku'] = $product->getSku();
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

        return $productArray;
    }
}
