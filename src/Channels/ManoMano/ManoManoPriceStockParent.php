<?php

namespace App\Channels\ManoMano;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\BusinessCentral\ProductStockFinder;
use App\BusinessCentral\ProductTaxFinder;
use App\Entity\Product;
use App\Entity\SaleChannel;
use App\Helper\MailService;
use App\Service\Aggregator\ApiAggregator;
use App\Service\Aggregator\PriceStockParent;
use App\Service\Carriers\UpsGetTracking;
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
        ProductTaxFinder $productTaxFinder,
        $projectDir
    ) {
        $this->projectDir =  $projectDir.'/public/manomano/catalogue/';
        parent::__construct($manager, $logger, $mailer, $businessCentralAggregator, $apiAggregator, $productStockFinder, $productTaxFinder);
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
        $saleChannel = $saleChannels[0];

        $offers = [];
        $finalHeader =[];
        foreach ($products as $product) {
            $offer = $this->flatProduct($product, $saleChannel);
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



        $skusIntegrated = [];

       
        $items = [];
        foreach($offers as $offer) {
            $skusIntegrated[]= $offer['sku'];
            $items[]=[
                'sku' => $offer['sku'],
                "stock" => [
                    "quantity" => $offer['quantity']
                ]
            ];
        }

        $offerManomanos = $this->getManoManoApi()->getAllOffers();
        foreach($offerManomanos as $offerManomano) {
            if(!in_array($offerManomano['sku'], $skusIntegrated) && $offerManomano['offer_is_online']=true) {
                $items[]=[
                    'sku' => $offerManomano['sku'],
                    "stock" => [
                        "quantity" => 0
                    ]
                ];
            }
        }


        $reponse = $this->getManoManoApi()->sendStocks($items);
        $this->logger->info('stock send '.json_encode($reponse));
    }

   


    public function flatProduct(Product $product, SaleChannel $saleChannel): ?array
    {
        $productMarketplace = $product->getProductSaleChannelByCode($saleChannel->getCode());

        if ($productMarketplace->getEnabled()) {



            $quantity = $this->getStockProductWarehouse($product->getSku());
           
            $offer = [
                'sku' =>$product->getSku(),
                "min_quantity" => 1,
                "quantity"=>  $quantity > 0 ? $quantity : 0,
                "shipping_time" => in_array($product->getSku(), ['ANK-PCK-7', 'ANK-PCK-8', 'ANK-PCK-9','ANK-PCK-10']) ? "10#20" : "3#5",
                "carrier" =>  'DHL Parcel',
                "shipping_price_vat_inc" => 0,
                "use_grid" => 0,
            ];
    
            $promotion = $productMarketplace->getBestPromotionForNow();
            if ($promotion) {
                $offer['product_price_vat_inc']= $promotion->getPromotionPrice() ;
                $offer['retail_price_vat_inc']= $productMarketplace->getPrice() ;
                $offer['sales']= 1 ;
            } else {
                $offer['product_price_vat_inc']= $productMarketplace->getPrice() ;
                $offer['sales']= 0 ;
            }
            return $offer;
        } else {
            return null;
        }
    }
}
