<?php

namespace App\Channels\Mirakl\Decathlon;

use App\Channels\Mirakl\MiraklPriceParent;
use App\Entity\IntegrationChannel;
use App\Entity\Product;
use League\Csv\Writer;
use Symfony\Component\Filesystem\Filesystem;

class DecathlonPrice extends MiraklPriceParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_DECATHLON;
    }



    public function sendPrices(array $products, array $saleChannels)
    {
        $header = ['sku'];
        foreach ($saleChannels as $saleChannel) {
            $code = $saleChannel->getCode().'-';
            array_push($header, $code.'enabled', $code.'price', $code.'promoprice');
        }
        $csv = Writer::createFromString();
        $csv->setDelimiter(';');
        $csv->insertOne($header);
        $datasToExport=[implode(';', $header)];
        $this->logger->info("start export ".count($products)." products on ".count($saleChannels)." sale channels");
        foreach ($products as $product) {
            $productArray = $this->addProduct($product, $header, $saleChannels);
            $csv->insertOne(array_values($productArray));
        }

        $dataArray = implode("\r\n", $datasToExport);
        $filename = $this->projectDir.'pricing_'.date('Ymd_His').'.csv';
        $this->logger->info("start export pricing locally");
        $fs = new Filesystem();
        $fs->appendToFile($filename, $csv->toString());
        $this->logger->info("start export pricing on channeladvisor");
    }


    private function addProduct(Product $product, array $header, array $saleChannels): array
    {
        $productArray = array_fill_keys($header, null);
        $productArray['offer-sku'] = $product->getSku();
        $productArray['product-id'] = $product->getSku();
        $productArray['product-id-type'] = 'SKU';
        foreach ($saleChannels as $saleChannel) {
            $code = $saleChannel->getCode().'-';
            $productMarketplace = $product->getProductSaleChannelByCode($saleChannel->getCode());
          
            if ($productMarketplace->getEnabled()) {
                $productArray['price[channel='.$code.']']= $productMarketplace->getPrice() ;
                $promotion = $productMarketplace->getBestPromotionForNow();
                if ($promotion) {
                    $productArray['discount-price[channel='.$code.']']= $promotion->getPromotionPrice() ;
                    //$productArray['discount-start-date[channel='.$code.']']= $promotion->get() ;
                    //$productArray['discount-end-date[channel='.$code.']']= $promotion->getPromotionPrice() ;
                }
            } else {
                $productArray[$code.'enabled']= 0;
            }
        }

        return $productArray;
    }
}
