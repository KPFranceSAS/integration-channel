<?php

namespace App\Channels\Cdiscount;

use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\Channels\Cdiscount\CdiscountApi;
use App\Entity\IntegrationChannel;
use App\Service\Aggregator\ProductSyncParent;


/**
 * Services that will get through the API the order from Cdiscount
 *
 */
class CdiscountProduct extends ProductSyncParent
{

    public function syncProducts()
    {
        /** @var  array $products */
        $products = $this->getProductsEnabledOnChannel();
        $productToArrays=[];
        foreach ($products as $product) {
            $productToArrays[]= $this->flatProduct($product);
        }
        $packageId = $this->getCdiscountApi()->sendProducts($productToArrays);
        $this->logger->info('PackageId '.json_encode($packageId));
    }



    protected function getCdiscountApi(): CdiscountApi
    {
        return $this->getApi();
    }



    public function getChannel(): string
    {
        return  IntegrationChannel::CHANNEL_CDISCOUNT;
    }

    public function flatProduct(array $product):array
    {
        $this->logger->info('Flat product '.$product['identifier']);
        $flatProduct = [
            'sellerProductReference' => $product['identifier'],
            'gtin' => $this->getAttributeSimple($product, 'ean'),
            'title' => substr($this->getAttributeSimple($product, "article_name", 'fr_FR'), 0, 132),
            "brand" => $this->getAttributeChoice($product, 'brand', 'fr_FR'),
            "language" => "fr-FR",
            'sellerPictureUrls' => []
        ];

        $description = $this->getAttributeSimple($product, "short_description", 'fr_FR');
        $flatProduct['description'] = substr($this->sanitizeHtml($description),0,250);

        
        $familyPim =$product['family'];

        if($familyPim == 'solar_panel') {
            $flatProduct ['categoryCode'] =  '0H0107';
        } elseif($familyPim == 'power_station') {
            $flatProduct ['categoryCode'] =  "0H020H";
        } elseif($familyPim == 'robot_piscine') {
            $flatProduct ['categoryCode'] =  "0D0C09";
        } elseif($familyPim == 'cutting_machine') {
            $flatProduct ['categoryCode'] =  "190G02";
        }


        for ($i = 1; $i <= 9;$i++) {
            $attributeImageLoc = $this->getAttributeSimple($product, 'image_url_loc_'.$i, 'fr_FR');
            $attributeImage = $this->getAttributeSimple($product, 'image_url_'.$i);
            if($attributeImageLoc || $attributeImage){
                $flatProduct ['sellerPictureUrls'][]= [
                    'index'=> $i,
                    'url' => $attributeImageLoc ? $attributeImageLoc : $attributeImage
                ];
            }
        }

        
        
        return $flatProduct;
    }


    



    public function getProductsEnabledOnChannel()
    {
        $searchBuilder = new SearchBuilder();
        $searchBuilder
            ->addFilter('brand', 'NOT EMPTY')
            ->addFilter('ean', 'NOT EMPTY')
            ->addFilter('enabled_channel', '=', true, ['scope' => 'Marketplace'])
            ->addFilter('marketplaces_assignement', 'IN', ['cdiscount_fr_kp'])
            ->addFilter('enabled', '=', true);

        return $this->akeneoConnector->searchProducts($searchBuilder, 'Marketplace');
    }

}
