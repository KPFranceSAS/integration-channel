<?php

namespace App\Channels\Shopify;

use App\Channels\Shopify\ShopifyApiParent;
use App\Service\Aggregator\ProductSyncParent;

abstract class ShopifySyncProductParent extends ProductSyncParent
{
    protected $productsApi;

    protected $productsPim;
    protected $categoriesApi;
    protected $categoriesPim;

    protected $categoriesProducts;

    abstract protected function getLocale();

    abstract protected function getCategoryTree();

    
    protected function getShopifyApi(): ShopifyApiParent
    {
        return $this->getApi();
    }



    
    public function retrievAllChildren($parent)
    {
        $categories = $this->akeneoConnector->getAllChildrenCategoriesByParent($parent);
        foreach ($categories as $category) {
            $this->categoriesPim[$category['code']] = $category;
            $this->retrievAllChildren($category['code']);
        }
    }


    public function syncProducts()
    {
        $this->getAllProducts();
        $this->syncAllCategorys();
        $this->syncAllProducts();
    }


    protected function getAllProducts()
    {
        $this->logger->info('Retrieve products from PIM');
        $products = $this->getProductsEnabledOnChannel();
        $this->productsPim = [];
        foreach ($products as $product) {
            $this->productsPim[]=$product;
        }
        $this->logger->info('Found '.count($this->productsPim).' products from PIM');
    }


    protected function syncAllProducts()
    {
        $this->logger->info('Sync all products');
        $productSimples = [];
        $productVariants = [];

        $this->logger->info('Organise products');
        foreach ($this->productsPim as $product) {
            if ($product['parent']==null) {
                $productSimples[] = $product;
            } else {
                $parent = $this->getParentProduct($product['parent']);
                if (!array_key_exists($parent['code'], $productVariants)) {
                    $productVariants[$parent['code']]=[
                        'parent'=> $parent,
                        'categories'=> $parent['categories'],
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
        
        foreach ($productSimples as $productSimple) {
            $this->integrateProductSimple($productSimple);
        }

        foreach ($productVariants as $productVariant) {
            $this->integrateProductVariant($productVariant);
        }
    }




    protected function syncAllCategorys()
    {
        $this->logger->info('Get all categories for main category');
        $this->categoriesPim=[];
        $this->categoriesProducts=[];
        $this->retrievAllChildren($this->getCategoryTree());
        $this->logger->info('Retrieved '.count($this->categoriesPim).' categories for main category');
        
        $this->logger->info('Get all product categorys');
        
        
        foreach ($this->productsPim as $product) {
            foreach ($product['categories'] as $category) {
                if (array_key_exists($category, $this->categoriesPim) && !array_key_exists($category, $this->categoriesProducts)) {
                    $this->categoriesProducts[$category] = $this->categoriesPim[$category];
                }
            }
        }
        $this->logger->info('End categories');

        foreach ($this->categoriesProducts as $categoryProduct) {
            $this->integrateCategory($categoryProduct);
        }
        $this->cleanCategories($this->categoriesProducts);
    }






    protected function integrateCategory(array $category)
    {
        $categoryShopify = $this->checkIfCategoryPresent($category['code']);
        if (!$categoryShopify) {
            $this->createCategory($category);
        } else {
            $this->updateCategory($categoryShopify, $category);
        }
    }


    protected function cleanCategories(array $categories)
    {
        $this->categoriesApi  =  $this->getShopifyApi()->getAllCustomCategory();
        foreach ($this->categoriesApi as $categoryApi) {
            if (!array_key_exists($categoryApi['handle'], $categories)) {
                $deletion =  $this->getShopifyApi()->deleteCustomCategory($categoryApi['id']);
            }
        }
    }

    



    protected function integrateProductVariant(array $product)
    {
        $productShopify = $this->checkIfProductPresent($product['parent']['code']);

        if (!$productShopify) {
            $productShopify = $this->createProductVariant($product);
        } else {
            $productShopify = $this->updateProductVariant($productShopify, $product);
        }
        $this->associateProductCollection($productShopify, $product['categories']);
    }

   
    protected function checkIfCategoryPresent($code)
    {
        if (!$this->categoriesApi) {
            $categoriesApi =  $this->getShopifyApi()->getAllCustomCategory();
            $this->categoriesApi = $categoriesApi ? $categoriesApi : [];
        }
        

        foreach ($this->categoriesApi as $categoryApi) {
            if ($categoryApi['handle']== strtolower($code)) {
                return $categoryApi;
            }
        }

        return null;
    }



    protected function createCategory(array $category)
    {
        $this->logger->info('Create category '.$category['code']);
        $categoryToCreate = [
            'body_html' => $category['descriptions'][$this->getLocale()],
            'title' => $category['labels'][$this->getLocale()],
            'handle' =>  $category['code'],
        ];
        $response = $this->getShopifyApi()->createCustomCategory($categoryToCreate);
        return $response->getDecodedBody();
    }


    protected function updateCategory(array $categoryShopify, array $category)
    {
        $this->logger->info('Update category '.$category['code']);
        $categoryToUpdate = [
            'body_html' => $category['descriptions'][$this->getLocale()],
            'title' => $category['labels'][$this->getLocale()],
        ];
        $response = $this->getShopifyApi()->updateCustomCategory($categoryShopify['id'], $categoryToUpdate);
        return $response->getDecodedBody();
    }




    protected function checkIfProductPresent($sku)
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
        $axesVariations = $this->getAxesVariation($parent['family'], $parent['family_variant']);
        $productModel = $product['variants'][0];

        $productToCreate = [
            'body_html' => $this->getDescription($productModel, $this->getLocale()),
            'title' => $this->getTitle($productModel, $this->getLocale(), true),
            'handle' =>  $parent['code'],
            'product_type' => $this->getFamilyName($parent['family'], $this->getLocale()),
            'variants' => [],
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

        return $productCreated;
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
            'product_type' => $this->getFamilyName($productModel['family'], $this->getLocale()),
        ];
        $response = $this->getShopifyApi()->updateProduct($productShopify['id'], $productToUpdate);
        $body = $response->getDecodedBody();
        return $body['product'];
    }



    protected function integrateProductSimple(array $product)
    {
        $productShopify = $this->checkIfProductPresent($product['identifier']);
        if ($productShopify) {
            $productShopify = $this->updateProductSimple($productShopify, $product);
        } else {
            $productShopify = $this->createProductSimple($product);
        }
        $this->associateProductCollection($productShopify, $product['categories']);
    }




    protected function associateProductCollection(array $productShopify, array $categories)
    {
        $collectProductShopify = $this->getShopifyApi()->getAllCollectsByProduct($productShopify['id']);
        foreach ($categories as $categorie) {
            $this->logger->info('Association with collection '.$categorie);
            if (array_key_exists($categorie, $this->categoriesProducts)) {
                $this->logger->info('|__Corresponding with tree '.$categorie);
                $found= false;
                $catgeoryShopify = $this->checkIfCategoryPresent($categorie);
                foreach ($collectProductShopify as $key => $collect) {
                    if ($collect['collection_id'] == $catgeoryShopify['id']) {
                        $this->logger->info('|__Link created with collection '.$catgeoryShopify['handle']);
                        unset($collectProductShopify[$key]);
                        $found = true;
                    }
                }

                if ($found===false) {
                    $this->logger->info('|__Create Link with collection '.$catgeoryShopify['handle']);
                    $reponse = $this->getShopifyApi()->createCollect(
                        [
                            'collection_id'=> $catgeoryShopify["id"],
                            'product_id'=> $productShopify["id"],
                        ]
                    );
                }
            } else {
                $this->logger->info('|__Not corresponding with tree '.$categorie);
            }
        }

        foreach ($collectProductShopify as $collectProduct) {
            $this->getShopifyApi()->deleteCollect($collectProduct['id']);
        }
    }

    protected function createProductSimple(array $product)
    {
        $this->logger->info('Create product simple '.$product['identifier']);
        $productToCreate = [
            'body_html' => $this->getDescription($product, $this->getLocale()),
            'title' => $this->getTitle($product, $this->getLocale()),
            'handle' =>  $product['identifier'],
            'product_type' => $this->getFamilyName($product['family'], $this->getLocale()),
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
        $body = $response->getDecodedBody();
        return $body['product'];
    }


    protected function updateProductSimple(array $productShopify, array $product)
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
        $response = $this->getShopifyApi()->updateProduct($productShopify['id'], $productToUpdate);
        $body = $response->getDecodedBody();
        return $body['product'];
    }
}
