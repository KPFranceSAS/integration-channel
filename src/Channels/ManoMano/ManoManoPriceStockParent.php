<?php

namespace App\Channels\ManoMano;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\BusinessCentral\ProductStockFinder;
use App\Entity\Product;
use App\Helper\MailService;
use App\Service\Aggregator\ApiAggregator;
use App\Service\Aggregator\PriceStockParent;
use Doctrine\Persistence\ManagerRegistry;
use League\Csv\Writer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

abstract class ManoManoPriceStockParent extends PriceStockParent
{
    protected $projectDir;

    public function __construct(
        ManagerRegistry $manager,
        LoggerInterface $logger,
        MailService $mailer,
        BusinessCentralAggregator $businessCentralAggregator,
        ApiAggregator $apiAggregator,
        ProductStockFinder $productStockFinder,
        $projectDir
    ) {
        $this->projectDir =  $projectDir.'/public/manomano/catalogue/';
        parent::__construct($manager, $logger, $mailer, $businessCentralAggregator, $apiAggregator, $productStockFinder);
    }



    protected function getLowerChannel()
    {
        return strtolower($this->getChannel());
    }


    protected function getManoManoApi(): ManoManoApiParent
    {
        return $this->getApi();
    }


    public function sendStocksPrices(array $products, array $saleChannels)
    {
        $offers = [];
        $finalHeader =[];
        foreach ($products as $product) {
            $offer = $this->addProduct($product, $saleChannels);
            if ($offer) {
                $headerProduct = array_keys($offer);
                foreach ($headerProduct as $headerP) {
                    if (!in_array($headerP, $finalHeader)) {
                        $finalHeader[] = $headerP;
                    }
                }
                $offers[]= $offer;
            }
        }
        $this->logger->info("start export ".count($offers)." products");

        $csv = Writer::createFromString();
        $csv->setDelimiter(';');
        $csv->insertOne($finalHeader);
        
        foreach ($offers as $offer) {
            $productArray = array_fill_keys($finalHeader, '');
            foreach ($finalHeader as $column) {
                if (array_key_exists($column, $offer)) {
                    $productArray[$column]=$offer[$column];
                }
            }
            $csv->insertOne(array_values($productArray));
        }
        $csvContent = $csv->toString();
        $filename = $this->projectDir.'export_prices_'.$this->getLowerChannel().'.csv';
        $this->logger->info("start export products locally");

        $fs = new Filesystem();
        $fs->dumpFile($filename, $csvContent);
    }

   


    protected function addProduct(Product $product, array $saleChannels): ?array
    {
        $saleChannel =  $saleChannels[0];
        $productMarketplace = $product->getProductSaleChannelByCode($saleChannel->getCode());
        if ($productMarketplace->getEnabled()) {
            $offer = [
                'sku' =>$product->getSku(),
                "min_quantity" => 1,
                "quantity"=> $this->getStockProductWarehouse($product->getSku()),
            ];
    
            $promotion = $productMarketplace->getBestPromotionForNow();
            if ($promotion) {
                $offer['product_price_vat_inc']= $promotion->getPromotionPrice() ;
                $offer['retail_price_vat_inc']= $productMarketplace->getPrice() ;
            } else {
                $offer['product_price_vat_inc']= $productMarketplace->getPrice() ;
            }
            return $offer;
        } else {
            return null;
        }
    }
}
