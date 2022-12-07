<?php

namespace App\Channels\Arise;

use App\Channels\Arise\AriseApiParent;
use App\Service\Aggregator\ProductSyncParent;

abstract class AriseSyncProductParent extends ProductSyncParent
{
    protected $productsApi;
    protected $categoriesApi;

    abstract protected function getLocale();

    
    protected function getAriseApi(): AriseApiParent
    {
        return $this->getApi();
    }



    public function syncProducts()
    {
        $this->logger->info('Get all products');
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
        $this->logger->info('End organise products');
        $this->logger->info('Process simple products '.count($productSimples));
        foreach ($productSimples as $productSimple) {
            $this->integrateProductSimple($productSimple);
        }
        $this->logger->info('Process variant products '.count($productVariants));
        foreach ($productVariants as $productVariant) {
            //$this->integrateProductVariant($productVariant);
        }
    }


 



    public function integrateProductVariant(array $product)
    {
        $productShopify = $this->checkIfProductPresent($product['parent']['code']);

        if (!$productShopify) {
            $productShopify = $this->createProductVariant($product);
        } else {
            $productShopify = $this->updateProductVariant($productShopify, $product);
        }
    }


    public function getParentProduct($productModelSku)
    {
        $parent = $this->akeneoConnector->getProductModel($productModelSku);
        return $parent['parent'] ? $this->akeneoConnector->getProductModel($parent['parent']) : $parent;
    }


    protected function checkIfProductPresent($skuProduct)
    {
        if (!$this->productsApi) {
            $productsApi =  $this->getAriseApi()->getAllProducts();
            $this->productsApi = $productsApi ? $productsApi : [];
        }

        foreach ($this->productsApi as $productApi) {
            foreach ($productApi->skus as $sku) {
                
                if ($sku->SellerSku == strtolower($skuProduct)) {
                    return $productApi;
                }
            }
        }

        return null;
    }

    protected function createProductVariant($product)
    {
        $parent = $product['parent'];
        $this->logger->info('Create product variant '.$parent['code']);
        $axesVariations = $this->getAxesVariation($parent['family'], $parent['family_variant']);
        $productModel = $product['variants'][0];

        $productToCreate = [
            'body_html' => $this->getDescription($productModel, $this->getLocale()),
            'title' => $this->getTitle($productModel, $this->getLocale(), true),
            'handle' =>  $parent['code'],
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

        $response =  $this->getAriseApi()->createProduct($productToCreate);
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

                    $response = $this->getAriseApi()->createImagesProduct($productCreated['id'], $imageCreated);
                }
            }
        }

        return $productCreated;
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
        ];
        $response = $this->getAriseApi()->updateProduct($productShopify['id'], $productToUpdate);
        $body = $response->getDecodedBody();
        return $body['product'];
    }



    protected function integrateProductSimple(array $product)
    {
        $productArise = $this->checkIfProductPresent($product['identifier']);
        if ($productArise) {
            $productArise = $this->updateProductSimple($productArise, $product);
        } else {
            $productArise = $this->createProductSimple($product);
        }
    }





    protected function createProductSimple(array $product)
    {
        $this->logger->info('Create product simple '.$product['identifier']);

        $images = [];

        for ($i=1;$i<10;$i++) {
            $imageUrl = $this->getAttributeSimple($product, 'image_url_'.$i);
            if ($imageUrl) {
                $images[$imageUrl]=[
                    'src' => $imageUrl
                ];
            }
        }


        $productToCreate = [
            "PrimaryCategory" => (int)$this->getAttributeSimple($product, 'arise_category_id'),
            "Attributes" => [
                'name' => $this->getTitle($product, $this->getLocale()),
                'description' => $this->getDescription($product, $this->getLocale()),
            ],
            'Skus' => [
                'Sku' => [
                    [
                        "sku" => $product['identifier'],
                        "ean_code" => $this->getAttributeSimple($product, 'ean'),
                        "quantity" => 0,
                        "price" => $this->getAttributePrice($product, 'msrp', 'EUR'),
                        "package_length" => $this->getAttributeUnit($product, 'package_lenght', 'CENTIMETER'),
                        "package_width" => $this->getAttributeUnit($product, 'package_width', 'CENTIMETER'),
                        "package_height" => $this->getAttributeUnit($product, 'package_height', 'CENTIMETER'),
                        "package_weight" => $this->getAttributeUnit($product, 'package_weight', 'KILOGRAM'),
                    ]
                ]
            ],
            'images' => [],
        ];

        dump($productToCreate);

        //$response = $this->getAriseApi()->createProduct($productToCreate);
        //$body = $response->getDecodedBody();
        //return $body['product'];
    }


    protected function updateProductSimple(stdClass $productArise, array $product)
    {
        $this->logger->info('Update product simple '.$product['identifier']);
        $productToUpdate = [
            'body_html' => $this->getDescription($product, $this->getLocale()),
            'title' => $this->getTitle($product, $this->getLocale()),
            'id' => $productShopify['id'],
            'product_type' => $this->getFamilyName($product['family'], $this->getLocale()),
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
        $response = $this->getAriseApi()->updateProduct($productShopify['id'], $productToUpdate);
        $body = $response->getDecodedBody();
        return $body['product'];
    }
}
