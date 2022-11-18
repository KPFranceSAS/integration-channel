<?php

namespace App\Channels\Shopify\FitbitCorporate;

use Akeneo\Pim\ApiClient\Search\SearchBuilder;
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
            //$this->integrateProductSimple($productSimple);
        }


        foreach ($productVariants as $productVariant) {
            $this->integrateProductVariant($productVariant);
        }
    }


    public function getParentProduct($productModelSku)
    {
        $parent = $this->akeneoConnector->getProductModel($productModelSku);
        return $parent['parent'] ? $this->akeneoConnector->getProductModel($parent['parent']) : $parent;
    }




    public function getFamilyApi($identifier, $langage)
    {
        $family =  $this->akeneoConnector->getFamily($identifier);
        return array_key_exists($langage, $family['labels']) ? $family['labels'][$langage] : $identifier;
    }


    public function getProductsEnabledOnChannel()
    {
        $searchBuilder = new SearchBuilder();
        $searchBuilder
            ->addFilter('brand', 'IN', ['fitbit'])
            ->addFilter('enabled_channel', '=', true, ['scope' => 'fitbit'])
            ->addFilter('enabled', '=', true);

        return $this->akeneoConnector->searchProducts($searchBuilder, 'fitbit');
    }

    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_FITBITCORPORATE;
    }

    public function checkIfParentPresent($sku)
    {
        if (!$this->productsApi) {
            $this->productsApi =  $this->getShopifyApi()->getAllProducts();
        }
        

        foreach ($this->productsApi as $productApi) {
            if ($productApi['handle']== strtolower($sku)) {
                return $productApi;
            }
        }

        return null;
    }


    public function getLocale()
    {
        return 'es_ES';
    }


    public function integrateProductVariant(array $product)
    {
        $productShopify = $this->checkIfParentPresent($product['parent']['code']);

        if (!$productShopify) {
            $this->createProductVariant($product);
        }
    }





    public function createProductVariant($product)
    {
        $parent = $product['parent'];
        $familyVariant = $this->akeneoConnector->getFamilyVariant($parent['family'], $parent['family_variant']);
        $axesVariations = $this->getAxes($familyVariant);
        $productModel = $product['variants'][0];
        $this->logger->info('Create product variant '.$parent['code']);
        $productToCreate = [
            'body_html' => $this->getDescription($productModel),
            'title' => $this->getTitle($productModel),
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


    public function explodeNamePim($image)
    {
        $explode= explode("\/", $image);
        return end($explode);
    }


    public function explodeNameShopify($image)
    {
        $shopify = explode("?", $this->explodeNamePim($image));
        return reset($shopify);
    }

    public function getAxes(array $variantFamily): array
    {
        $axes = [];
        foreach ($variantFamily['variant_attribute_sets'] as $variantAttribute) {
            foreach ($variantAttribute['axes'] as $axe) {
                $axes[]= $axe;
            }
        }
        return $axes;
    }

    public function integrateProductSimple(array $product)
    {
        $productShopify = $this->checkIfParentPresent($product['identifier']);
        if ($productShopify) {
            $this->logger->info('Update product simple '.$product['identifier']);
            $productToUpdate = [
                'body_html' => $this->getDescription($product),
                'title' => $this->getTitle($product),
                'id' => $productShopify['id'],
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
            $this->getShopifyApi()->updateProduct($productShopify['id'], $productToUpdate);
        } else {
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
        }
    }





    public function getTitle($productPim)
    {
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


    public function getAttributePrice($productPim, $nameAttribute, $currency)
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





    public function getDescription($productPim)
    {
        $description = $this->getAttributeSimple($productPim, 'description', 'es_ES');
        if ($description) {
            return $description;
        }

        $decriptionDefault = $this->getAttributeSimple($productPim, 'description_defaut', 'es_ES');
        if ($decriptionDefault) {
            return '<p>'.$decriptionDefault.'</p>';
        }

        return null;
    }


    public function getAttributeSimple($productPim, $nameAttribute, $locale=null)
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


    public function getTranslationLabel($nameAttribute, $locale)
    {
        $attribute = $this->akeneoConnector->getAttribute($nameAttribute);
        return array_key_exists($locale, $attribute['labels']) ? $attribute['labels'][$locale] : $nameAttribute;
    }


   

    public function getTranslationOption($attributeCode, $code, $locale)
    {
        $attribute = $this->akeneoConnector->getAttributeOption($attributeCode, $code);
        return array_key_exists($locale, $attribute['labels']) ? $attribute['labels'][$locale] : $code;
    }
}
