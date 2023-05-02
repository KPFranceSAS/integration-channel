<?php

namespace App\Channels\Cdiscount;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\BusinessCentral\ProductStockFinder;
use App\BusinessCentral\ProductTaxFinder;
use App\Entity\IntegrationChannel;
use App\Entity\Product;
use App\Entity\SaleChannel;
use App\Helper\MailService;
use App\Service\Aggregator\ApiAggregator;
use App\Service\Aggregator\PriceStockParent;
use Doctrine\Persistence\ManagerRegistry;
use League\Csv\Writer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use ZipArchive;

class CdiscountPriceStock extends PriceStockParent
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
        $this->projectDir =  $projectDir.'/public/cdiscount/';
        parent::__construct($manager, $logger, $mailer, $businessCentralAggregator, $apiAggregator, $productStockFinder,$productTaxFinder);
    }


    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_CDISCOUNT;
    }



    protected function getLowerChannel()
    {
        return strtolower($this->getChannel());
    }


    protected function getCdiscountApi(): CdiscountApi
    {
        return $this->getApi();
    }



    public function sendStocksPrices(array $products, array $saleChannels)
    {
        $saleChannel = $saleChannels[0];

        $offers = [];
        foreach ($products as $product) {
            $offer = $this->flatProduct($product, $saleChannel);
            if ($offer) {
                $offers[]= $offer;
            }
        }
        $this->logger->info("start export ".count($offers)." products");
        
       
        $filename = $this->projectDir.'export_prices_'.$this->getLowerChannel().'.csv';
        $this->logger->info("start export products locally");
    }

   


    public function flatProduct(Product $product, SaleChannel $saleChannel): ?array
    {
        $productMarketplace = $product->getProductSaleChannelByCode($saleChannel->getCode());
        if ($productMarketplace->getEnabled()) {

            $productApi = $this->getCdiscountApi()->searchProductByGtin($product->getEan());
            if(!$productApi){
                $this->logger->info('Product do no exists on Cdiscount');
                return null;
            }


            $offer = [
                
                "Stock"=> $this->getStockProductWarehouse($product->getSku()),
                "ProductEan" => $product->getEan()
            ];

            



    
            $promotion = $productMarketplace->getBestPromotionForNow();
            if ($promotion) {
                $offer['PromotionPrice']= $promotion->getPromotionPrice() ;
                $offer['Price']= $productMarketplace->getPrice() ;
            } else {
                $offer['PromotionPrice']= $productMarketplace->getPrice() ;
                $offer['Price']= $productMarketplace->getPrice() ;
            }
            return $offer;
        } else {
            return null;
        }
    }




    public function generateZipArchiveContent($offers){

        $content= '<OfferPackage Name="Nom fichier offres" PurgeAndReplace="false" xmlns="clr-namespace:Cdiscount.Service.OfferIntegration.Pivot;assembly=Cdiscount.Service.OfferIntegration" xmlns:x="http://schemas.microsoft.com/winfx/2006/xaml">
            <OfferPackage.Offers>
                <OfferCollection Capacity="'.count($offers).'">';
        foreach($offers as $offer){
            $content= '<Offer SellerProductId="'.$offer["SellerProductId"].'" ProductEan="'.$offer["ProductEan"].'" ProductCondition="6" Price="'.$offer["PromotionPrice"].'" EcoPart="0" DeaTax="0" Vat="19.6" Stock="'.$offer["Stock"].'" StrikedPrice="'.$offer["Price"].'" Comment="'.$offer["Sku"].'" PreparationTime="2"></Offer>';
        }
        $content.='</OfferCollection>
            </OfferPackage.Offers>
        </OfferPackage>';

        $zipArchive = new ZipArchive();
        $zipArchive->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="utf-8"?>
            <Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
                <Default Extension="xml" ContentType="text/xml" />
                <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml" />
            </Types>');
         $zipArchive->addFromString('_rels/.rels', '<?xml version="1.0" encoding="utf-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Type="http://cdiscount.com/uri/document" Target="/Content/Offers.xml" Id="Rc7b01b1610144e98" /></Relationships>');
    }



}
