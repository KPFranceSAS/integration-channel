<?php

namespace App\Channels\Arise;

use App\Channels\Arise\AriseApiParent;
use App\Service\Aggregator\ProductSyncParent;
use stdClass;

abstract class AriseSyncProductParent extends ProductSyncParent
{
    protected $productsApi;
    protected $categoriesApi;

    abstract protected function getLocale();

    protected function getAriseApi(): AriseApiParent
    {
        return $this->getApi();
    }


    protected function getNbLevels()
    {
      return 1;
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
            $this->integrateProductVariant($productVariant);
        }
    }


 



    public function integrateProductVariant(array $product)
    {
        $productArise = $this->checkIfProductPresent($product['parent']['code']);
        if (!$productArise) {
            $productResult = $this->createProductVariant($product);
        } else {
            $productResult = $this->updateProductVariant($productArise, $product);
        }
        dump($productResult);
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
                if ($sku->SellerSku == $skuProduct) {
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
        
        $productToCreate = $this->getArrayProductGlobal($productModel, true);
        $productToCreate['images'] = $this->getTransferedUrlImages($productModel);

        $valueVariants = [];
        foreach ($axesVariations as $key => $axeVariation) {
            $i = $key+1;
            $valueVariants["Variation".$i] = [
                                'name'=> $axeVariation ,
                                
                                'hasImage' =>true,
                                "customize" => false,
                                "options" => [
                                    'option' => []
                                ]
             ];
        }

        foreach ($product['variants'] as $variant) {
            $this->logger->info('Add  variant '.$variant['identifier']);
            $variantToCreate=$this->getArrayProductSku($variant);
            $variantToCreate['Images']['Image']= $this->getTransferedUrlImages($variant, 1);
           

            foreach ($axesVariations as $key => $axeVariation) {
                $i = $key+1;
                $value = $this->getAttributeSimple($variant, $axeVariation);
                $translatedValue = $this->getTranslationOption($axeVariation, $value, $this->getLocale());
                $variantToCreate[$axeVariation] = $translatedValue;
                if (!in_array($translatedValue, $valueVariants["Variation".$i]['options']['option'])) {
                    $valueVariants["Variation".$i]['options']['option'][]=$translatedValue;
                }
            }

            $productToCreate['Skus']['Sku'][]=$variantToCreate;
        }
        $productToCreate['Variation']=$valueVariants;

        dump($productToCreate);
        return  $this->getAriseApi()->createProduct($productToCreate);
    }



    protected function updateProductVariant(array $productShopify, array $product)
    {
        $parent = $product['parent'];
        $productModel = $product['variants'][0];
        $this->logger->info('Update product variant '.$parent['code']);
        $productToUpdate = [
            'body_html' => $this->getDescription($productModel, $this->getLocale()),
            'title' => $this->getTitle($productModel, $this->getLocale(), true),
            'id' => $productShopify['id'],
        ];
        $response = $this->getAriseApi()->updateProduct($productToUpdate);
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
        $images = $this->getTransferedUrlImages($product);
        $productToCreate = $this->getArrayProductGlobal($product);
        $productToCreate['Skus']['Sku'][]=$this->getArrayProductSku($product);
        
        if (count($images)>0) {
            $productToCreate['images'] = $images;
        }
        return $this->getAriseApi()->createProduct($productToCreate);
    }



    protected function getTransferedUrlImages(array $product, $nbImages=9){
        $images = [];
        for ($i=1;$i<=$nbImages;$i++) {
            $imageUrl = $this->getAttributeSimple($product, 'image_url_'.$i);
            if ($imageUrl) {
                $imageMigrated = $this->getAriseApi()->migrateImage($imageUrl);
                if ($imageMigrated) {
                    $images[]=$imageMigrated;
                }
            }
        }
        return $images;
    }






    protected function getArrayProductGlobal(array $product, $isModel=false)
    {
        return [
            "PrimaryCategory" => (int) $this->getAttributeSimple($product, 'arise_category_id'),
            "Attributes" => [
                'name' => $this->getTitle($product, $this->getLocale(), $isModel),
                'description' => $this->getDescription($product, $this->getLocale()),
                'short_description' => $this->getAttributeSimple($product, 'short_description', $this->getLocale()),
                'brand' => $this->getAttributeChoice($product, 'brand', $this->getLocale()),
                "delivery_option_sof" => "No",
                "delivery_option_standard" => "No",
                "delivery_option_express" => "Yes",
                "Hazmat" => "None",
            ],
            'Skus' => [
                'Sku' => []
            ]
        ];
    }


    protected function getArrayProductSku(array $product)
    {
        return [
            "SellerSku" => $product['identifier'],
            "ean_code" => $this->getAttributeSimple($product, 'ean'),
            "quantity" => 0,
            "price" => $this->getAttributePrice($product, 'msrp', 'EUR'),
            "package_length" => (int) $this->getAttributeUnit($product, 'package_lenght', 'CENTIMETER'),
            "package_width" => (int) $this->getAttributeUnit($product, 'package_width', 'CENTIMETER'),
            "package_height" => (int) $this->getAttributeUnit($product, 'package_height', 'CENTIMETER'),
            "package_weight" => $this->getAttributeUnit($product, 'package_weight', 'KILOGRAM'),
        ];
    }





    protected function updateProductSimple(stdClass $productArise, array $product)
    {
        $this->logger->info('Update product simple '.$product['identifier']);
        $nbImagesArise = count($productArise->images);
        $imagesPim = [];

        for ($i=1;$i<10;$i++) {
            $imageUrl = $this->getAttributeSimple($product, 'image_url_'.$i);
            if ($imageUrl) {
                $imagesPim[]=$imageUrl;
            }
        }

        

        $images=[];

        if (count($imagesPim)!=$nbImagesArise) {
            foreach ($imagesPim as $imagePim) {
                $imageMigrated = $this->getAriseApi()->migrateImage($imagePim);
                if ($imageMigrated) {
                    $images[]=$imageMigrated;
                }
            }
        }

        $productToUpdate = [
            "ItemId" => $productArise->item_id,
            "Attributes" => [
                'name' => $this->getTitle($product, $this->getLocale()),
                'description' => $this->getDescription($product, $this->getLocale()),
                'short_description' =>$this->getAttributeSimple($product, 'short_description', $this->getLocale()),
            ],
            'Skus' => [
                "Sku" => [
                    [
                        "SellerSku" =>$product['identifier']
                    ]
                ]
            ]
        ];

        if (count($images)>0) {
            $productToUpdate['Images']["Image"] = $images;
        }
        $response = $this->getAriseApi()->updateProduct($productToUpdate);
        return $response;
    }
}
