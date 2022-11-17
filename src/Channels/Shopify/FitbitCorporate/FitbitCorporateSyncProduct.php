<?php

namespace App\Channels\Shopify\FitbitCorporate;

use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\Channels\Shopify\ShopifyStockParent;
use App\Channels\Shopify\ShopifySyncProductParent;
use App\Entity\IntegrationChannel;

class FitbitCorporateSyncProduct extends ShopifySyncProductParent
{
    public function syncProducts()
    {
        $products = $this->getProductsEnabledOnChannel();
        $productSimples = [];
        $productVariants = [];

        foreach ($products as $product) {
            if($product['parent']==null){
                $productSimples[] = $product;
            } else {
                if(!array_key_exists($product['parent'], $productVariants )){
                    $productVariants[$product['parent']]=[
                        'parent'=>$this->akeneoConnector->getProductModel($product['parent']),
                        'variants' => []
                    ];
                }
                $productVariants[$product['parent']]["variants"][]=$product;
            }
        }


        foreach($productSimples as $productSimple){
            $this->integrateProductSimple($productSimple);
        }
        
    }



    public function getFamilyApi($identifier, $langage){

        $family =  $this->akeneoConnector->getFamily($identifier);
        return array_key_exists($langage, $family['labels']) ? $family['labels'][$langage] : $identifier;
    }


    public function getProductsEnabledOnChannel()
    {
        $searchBuilder = new SearchBuilder();
        $searchBuilder
            ->addFilter('brand', 'IN', ['pax'])
            //->addFilter('enabled_channel', '=', true, ['scope' => 'fitbit'])
            ->addFilter('enabled', '=', true);

        return $this->akeneoConnector->searchProducts($searchBuilder, 'kps_green');
    }

    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_FITBITCORPORATE;
    }

    public function checkIfParentPresent($sku){
        if(!$this->productsApi){
            $this->productsApi =  $this->getShopifyApi()->getAllProducts();
        }
        

        foreach($this->productsApi as $productApi){
            
            if($productApi['handle']== strtolower($sku)){
                return $productApi;
            }
        }

        return null;
    }



    public function integrateProductSimple(array $product){

        $productShopify = $this->checkIfParentPresent($product['identifier']);
        if($productShopify){
            $this->logger->info('Update product simple '.$product['identifier']);
            $productToUpdate = [
                'body_html' => $this->getDescription($product),
                'title' => $this->getTitle($product),
                'id' => $productShopify['id'],
                'images' => []
            ];

            for($i=1;$i<10;$i++){
                $imageUrl = $this->getAttributeSimple($product, 'image_url_'.$i);
                if($imageUrl){
                    $productToUpdate['images'][]=[
                        'src' => $imageUrl
                    ];
                }
            }

            $this->getShopifyApi()->updateProduct($productShopify['id'], $productToUpdate);
        } else {
            $this->logger->info('Create product simple '.$product['identifier']);
            $productToCreate = [
                'body_html' => $this->getDescription($product),
                'title' => $this->getTitle($product),
                'handle' =>  $product['identifier'],
                'product_type' => $this->getFamilyApi($product['family'], 'es_ES'),
                'variants' => [
                    [
                        "sku" => $product['identifier'],
                        "barcode" => $this->getAttributeSimple($product, 'ean'),
                        "inventory_management" => 'shopify',
                        "price" => $this->getAttributePrice($product, 'msrp', 'EUR'),
                    ]
                ],
                'images' => []
            ];

            for($i=1;$i<10;$i++){
                $imageUrl = $this->getAttributeSimple($product, 'image_url_'.$i);
                if($imageUrl){
                    $productToCreate['images'][]=[
                        'src' => $imageUrl
                    ];
                }
            }

            $this->getShopifyApi()->createProduct($productToCreate);
            
        }
        

    }


    public function getTitle($productPim){
        $title = $this->getAttributeSimple($productPim, 'article_name', 'es_ES');
        if($title){
            return $title;
        }

        $titleDefault = $this->getAttributeSimple($productPim, 'article_name_defaut', 'es_ES');
        if($titleDefault){
            return $titleDefault;
        }

        return $this->getAttributeSimple($productPim, 'erp_name');
    }


    public function getAttributePrice($productPim, $nameAttribute, $currency){
        $valueAttribute = $this->getAttributeSimple($productPim, $nameAttribute);
        if($valueAttribute ){
            foreach($valueAttribute as $value){
                if($value['currency']==$currency){
                    return $value["amount"];
                }
            }
        }

        return null;
    }




    public function getDescription($productPim){
        $description = $this->getAttributeSimple($productPim, 'description', 'es_ES');
        if($description){
            return $description;
        }

        $decriptionDefault = $this->getAttributeSimple($productPim, 'description_defaut', 'es_ES');
        if($decriptionDefault){
            return '<p>'.$decriptionDefault.'</p>';
        }

        return null;
    }



    public function getAttributeSimple($productPim, $nameAttribute, $locale=null){
        if(array_key_exists($nameAttribute, $productPim['values'])){
            if($locale){
                foreach($productPim['values'][$nameAttribute] as $attribute){
                    if($attribute['locale']==$locale){
                        return $attribute['data'];
                    }
                }
            } else {
                return  $productPim['values'][$nameAttribute][0]["data"];
            }
           
        }
        return null;
    }



}
