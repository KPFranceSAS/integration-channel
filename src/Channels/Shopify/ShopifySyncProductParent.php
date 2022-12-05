<?php

namespace App\Channels\Shopify;

use App\Channels\Shopify\ShopifyApiParent;
use App\Service\Aggregator\ProductSyncParent;

abstract class ShopifySyncProductParent extends ProductSyncParent
{
    protected $logger;

    protected $akeneoConnector;

    protected $errors;

    protected $mailer;

    protected $businessCentralAggregator;

    protected $apiAggregator;

    protected $productsApi;
    


    abstract protected function getLocale();

    

    abstract protected function getNbLevels();

 
    protected function getShopifyApi(): ShopifyApiParent
    {
        return $this->getApi();
    }


    public function syncProducts()
    {
        $products = $this->getProductsEnabledOnChannel();
        $productSimples = [];
        $productVariants = [];

        foreach ($products as $product) {
            if ($product['parent']==null) {
                $productSimples[] = $product;
            } else {
                $parent = $this->getParentProduct($product['parent']);
                if (!array_key_exists($parent['code'], $productVariants)) {
                    $productVariants[$parent['code']]=[
                        'parent'=> $parent,
                        'variants' => [],
                        'parents' => []
                    ];
                }
                if ($product['parent']!=$parent["code"]) {
                    $productVariants[$parent['code']]["parents"][]=$this->akeneoConnector->getProductModel($product['parent']);
                }
                $productVariants[$parent['code']]["variants"][]=$product;
            }
        }


        foreach ($productSimples as $productSimple) {
            $this->integrateProductSimple($productSimple);
        }


        foreach ($productVariants as $productVariant) {
            $this->integrateProductVariant($productVariant);
        }
    }



    public function integrateProductVariant(array $product)
    {
        $productShopify = $this->checkIfParentPresent($product['parent']['code']);

        if (!$productShopify) {
            $this->createProductVariant($product);
        } else {
            $this->updateProductVariant($productShopify, $product);
        }
    }


    public function getParentProduct($productModelSku)
    {
        $parent = $this->akeneoConnector->getProductModel($productModelSku);
        if ($this->getNbLevels()==1) {
            return $parent;
        } else {
            return $parent['parent'] ? $this->akeneoConnector->getProductModel($parent['parent']) : $parent;
        }
    }




    public function getFamilyApi($identifier, $langage)
    {
        $family =  $this->akeneoConnector->getFamily($identifier);
        return array_key_exists($langage, $family['labels']) ? $family['labels'][$langage] : $identifier;
    }


   


    protected function checkIfParentPresent($sku)
    {
        if (!$this->productsApi) {
            $productsApi =  $this->getShopifyApi()->getAllProducts();
            $this->productsApi = $productsApi ? $productsApi : [];
        }
        

        foreach ($this->productsApi as $productApi) {
            if ($productApi['handle']== strtolower($sku)) {
                return $productApi;
            }
        }

        return null;
    }


  





    protected function createProductVariant($product)
    {
        $parent = $product['parent'];
        $this->logger->info('Create product variant '.$parent['code']);
        $familyVariant = $this->akeneoConnector->getFamilyVariant($parent['family'], $parent['family_variant']);
        $axesVariations = $this->getAxes($familyVariant);
        $productModel = $product['variants'][0];

        $productToCreate = [
            'body_html' => $this->getDescription($productModel),
            'title' => $this->getTitle($productModel, true),
            'handle' =>  $parent['code'],
            'product_type' => $this->getFamilyApi($parent['family'], $this->getLocale()),
            'variants' => [
                    
            ],
            'images' => []
        ];

        $valueVariants = [];
        foreach ($axesVariations as $key => $axeVariation) {
            $valueVariants[] = [
                                'name'=> $this->getTranslationLabel($axeVariation, $this->getLocale()) ,
                                "values" => []
                                ];
        }

        for ($i=2;$i<10;$i++) {
            $imageUrl = $this->getAttributeSimple($productModel, 'image_url_'.$i);
            if ($imageUrl) {
                $productToCreate['images'][]=[
                    'src' => $imageUrl
                ];
            }
        }

        $imageUrlsVariants = [];
        foreach ($product['variants'] as $variant) {
            $this->logger->info('Add  variant '.$variant['identifier']);
            $variantToCreate = [
                "sku" => $variant['identifier'],
                "barcode" => $this->getAttributeSimple($variant, 'ean'),
                "inventory_management" => 'shopify',
                "price" => $this->getAttributePrice($variant, 'msrp', 'EUR'),
            ];

            $imageUrl = $this->getAttributeSimple($variant, 'image_url_1');
            if ($imageUrl) {
                $imageUrlsVariants[$variant['identifier']] = $imageUrl;
            }

            foreach ($axesVariations as $key => $axeVariation) {
                $i = $key+1;
                $value = $this->getAttributeSimple($variant, $axeVariation);
                $translatedValue = $this->getTranslationOption($axeVariation, $value, $this->getLocale());
                $variantToCreate['option'.$i] = $translatedValue;
                if (!in_array($translatedValue, $valueVariants[$key]['values'])) {
                    $valueVariants[$key]['values'][]=$translatedValue;
                }
            }

            $productToCreate['variants'][]=$variantToCreate;
        }

            
        $productToCreate['options']=$valueVariants;

        $response =  $this->getShopifyApi()->createProduct($productToCreate);
        $body = $response->getDecodedBody();
        $productCreated = $body['product'];


            

        $mainImageFound = false;

        foreach ($imageUrlsVariants as $sku => $url) {
            foreach ($productCreated["variants"] as $variationCreated) {
                if ($variationCreated['sku'] == $sku) {
                    $this->logger->info('Add product variant image '.$sku);
                    $imageCreated = [
                        'src' => $url,
                        'product_id' => $productCreated['id'],
                        "variant_ids" => [
                            $variationCreated['id']
                        ]
                    ];
                    if (!$mainImageFound) {
                        $mainImageFound= true;
                        $imageCreated['position'] = 1;
                    }

                    $response = $this->getShopifyApi()->createImagesProduct($productCreated['id'], $imageCreated);
                }
            }
        }
    }



    protected function updateProductVariant(array $productShopify, array $product)
    {
        $parent = $product['parent'];
        $productModel = $product['variants'][0];
        $this->logger->info('Update product variant '.$parent['code']);
        $productToUpdate = [
            'body_html' => $this->getDescription($productModel),
            'title' => $this->getTitle($productModel, true),
            'id' => $productShopify['id'],
            'product_type' => $this->getFamilyApi($productModel['family'], $this->getLocale()),
        ];
        $response = $this->getShopifyApi()->updateProduct($productShopify['id'], $productToUpdate);
        return $response->getDecodedBody();
    }


    protected function getAxes(array $variantFamily): array
    {
        $axes = [];
        foreach ($variantFamily['variant_attribute_sets'] as $variantAttribute) {
            foreach ($variantAttribute['axes'] as $axe) {
                $axes[]= $axe;
            }
        }
        if ($this->getNbLevels()==1 && count($axes)==2) {
            unset($axes[0]);
            $axes= array_values($axes);
        }

        return $axes;
    }

    protected function integrateProductSimple(array $product)
    {
        $productShopify = $this->checkIfParentPresent($product['identifier']);
        if ($productShopify) {
            $this->updateProductSimple($productShopify, $product);
        } else {
            $this->createProductSimple($product);
        }
    }



    protected function createProductSimple(array $product)
    {
        $this->logger->info('Create product simple '.$product['identifier']);
        $productToCreate = [
            'body_html' => $this->getDescription($product),
            'title' => $this->getTitle($product),
            'handle' =>  $product['identifier'],
            'product_type' => $this->getFamilyApi($product['family'], $this->getLocale()),
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

        for ($i=1;$i<10;$i++) {
            $imageUrl = $this->getAttributeSimple($product, 'image_url_'.$i);
            if ($imageUrl) {
                $productToCreate['images'][]=[
                    'src' => $imageUrl
                ];
            }
        }

        $response = $this->getShopifyApi()->createProduct($productToCreate);
        return $response->getDecodedBody();
    }


    protected function updateProductSimple(array $productShopify, array $product)
    {
        $this->logger->info('Update product simple '.$product['identifier']);
        $productToUpdate = [
            'body_html' => $this->getDescription($product),
            'title' => $this->getTitle($product),
            'id' => $productShopify['id'],
            'product_type' => $this->getFamilyApi($product['family'], $this->getLocale()),
        ];

        $nbImageShopifys = count($productShopify['images']);
        $imagesPim = [];

        for ($i=1;$i<10;$i++) {
            $imageUrl = $this->getAttributeSimple($product, 'image_url_'.$i);
            if ($imageUrl) {
                $imagesPim[]=[
                    'src' => $imageUrl
                ];
            }
        }

        if (count($imagesPim)!=$nbImageShopifys) {
            $productToUpdate['images'] = $imagesPim;
        }
        $response = $this->getShopifyApi()->updateProduct($productShopify['id'], $productToUpdate);
        return $response->getDecodedBody();
    }











    protected function getTitle($productPim, $isModel=false)
    {
        if ($isModel) {
            $parentTitle = $this->getAttributeSimple($productPim, 'parent_name', $this->getLocale());
            if ($parentTitle) {
                return $parentTitle;
            }
        }
        


        $title = $this->getAttributeSimple($productPim, 'article_name', $this->getLocale());
        if ($title) {
            return $title;
        }

        $titleDefault = $this->getAttributeSimple($productPim, 'article_name_defaut', $this->getLocale());
        if ($titleDefault) {
            return $titleDefault;
        }

        return $this->getAttributeSimple($productPim, 'erp_name');
    }


    protected function getAttributePrice($productPim, $nameAttribute, $currency)
    {
        $valueAttribute = $this->getAttributeSimple($productPim, $nameAttribute);
        if ($valueAttribute) {
            foreach ($valueAttribute as $value) {
                if ($value['currency']==$currency) {
                    return $value["amount"];
                }
            }
        }

        return null;
    }





    protected function getDescription($productPim)
    {
        $description = $this->getAttributeSimple($productPim, 'description', $this->getLocale());
        if ($description) {
            return $description;
        }

        $decriptionDefault = $this->getAttributeSimple($productPim, 'description_defaut', $this->getLocale());
        if ($decriptionDefault) {
            return '<p>'.$decriptionDefault.'</p>';
        }

        return null;
    }


    protected function getAttributeSimple($productPim, $nameAttribute, $locale=null)
    {
        if (array_key_exists($nameAttribute, $productPim['values'])) {
            if ($locale) {
                foreach ($productPim['values'][$nameAttribute] as $attribute) {
                    if ($attribute['locale']==$locale) {
                        return $attribute['data'];
                    }
                }
            } else {
                return  $productPim['values'][$nameAttribute][0]["data"];
            }
        }
        return null;
    }


    protected function getTranslationLabel($nameAttribute, $locale)
    {
        $attribute = $this->akeneoConnector->getAttribute($nameAttribute);
        return array_key_exists($locale, $attribute['labels']) ? $attribute['labels'][$locale] : $nameAttribute;
    }


   

    protected function getTranslationOption($attributeCode, $code, $locale)
    {
        $attribute = $this->akeneoConnector->getAttributeOption($attributeCode, $code);
        return array_key_exists($locale, $attribute['labels']) ? $attribute['labels'][$locale] : $code;
    }
}
