<?php

namespace App\Channels\ManoMano;

use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\Entity\IntegrationChannel;
use App\Entity\Product;
use App\Entity\ProductTypeCategorizacion;
use App\Entity\SaleChannel;
use App\Service\Aggregator\ProductSyncParent;
use League\Csv\Writer;
use Symfony\Component\Filesystem\Filesystem;

abstract class ManoManoSyncProductParent extends ProductSyncParent
{
    abstract public function getChannel(): string;

    abstract public function getChannelPim(): string;

    abstract protected function getLocale(): string;

    protected $projectDir;


   
    protected function getLowerChannel()
    {
        return strtolower($this->getChannel());
    }


    public function syncProducts()
    {
        /** @var  array $products */
        $products = $this->getProductsEnabledOnChannel();
        $productToArrays=[];
        $finalHeader = [];

        /**@var ManoManoPriceStockParent */
        $priceUpdater = $this->priceStockAggregator->getPriceStock($this->getChannel());
        $integrationChannel = $this->manager->getRepository(IntegrationChannel::class)->findBy([
            'code' => $this->getChannel()
        ]);
        $saleChannels = $this->manager->getRepository(SaleChannel::class)->findBy([
            'integrationChannel' => $integrationChannel
        ]);


        $productCategorizations = $this->manager->getRepository(ProductTypeCategorizacion::class)->findAll();


        foreach($productCategorizations as $productCategorization) {
            if($productCategorization->getManomanoCategory() && strlen($productCategorization->getManomanoCategory())>0) {
                $this->categories[$productCategorization->getPimProductType()]=(int)$productCategorization->getManomanoCategory();
            }
        }
        

        foreach ($products as $product) {
            $productToArray = $this->flatProduct($product);


            $productDb = $this->manager->getRepository(Product::class)->findOneBy([
                'sku' => $product['identifier']
            ]);
            if ($productDb && count($saleChannels)>0) {
                /**@var SaleChannel */
                $saleChannel =  $saleChannels[0];
                $productPrice = $priceUpdater->flatProduct($productDb, $saleChannel);
                if ($productPrice) {
                    foreach ($productPrice as $key => $value) {
                        $productToArray[$key] = $value;
                    }
                }
            }

            $headerProduct = array_keys($productToArray);
            foreach ($headerProduct as $headerP) {
                if (!in_array($headerP, $finalHeader)) {
                    $finalHeader[] = $headerP;
                }
            }
            $productToArrays[]= $productToArray;
        }
        $this->sendProducts($productToArrays, $finalHeader);
    }


   
   

    protected function getProductsEnabledOnChannel()
    {
        $searchBuilder = new SearchBuilder();
        $searchBuilder
            ->addFilter('brand', 'NOT EMPTY')
            ->addFilter('ean', 'NOT EMPTY')
            ->addFilter('enabled_channel', '=', true, ['scope' => 'Marketplace'])
            ->addFilter('marketplaces_assignement', 'IN', [$this->getChannelPim()])
            ->addFilter('enabled', '=', true);

        return $this->akeneoConnector->searchProducts($searchBuilder, 'Marketplace');
    }



    

    

    protected function flatProduct(array $product):array
    {
        $this->logger->info('Flat product '.$product['identifier']);

        $flatProduct = [
            'sku' => $product['identifier'],
            'ean' => $this->getAttributeSimple($product, 'ean'),
            'sku_manufacturer' => $product['identifier'],
            'mm_category_id' => $this->getCategoryNode($this->getAttributeSimple($product, 'mkp_product_type'), 'manomano'),
            'merchant_category'=> $this->getAttributeChoice($product, 'mkp_product_type', $this->getLocale()),
            'min_quantity' => 1,
            "manufacturer_pdf" => null,
            "product_information_pdf" => $this->getAttributeSimple($product, 'user_guide_url', $this->getLocale()),
            "repairability_index_pdf" => null,
            "product_instructions_pdf" =>  $this->getAttributeSimple($product, 'user_guide_url', $this->getLocale()),
            "safety_information_pdf" => null,
            "refrigeration_devices_information_pdf" => null,
            "eu_energy_efficiency_class_url" => null,
            'unit_count' => 1,
            'unit_count_type'=> "piece",
            "pcs_per_pack" => 1,
            "pcs_per_pack_unit" => "products"
             ];



        $valueGarantee =  $this->getAttributeChoice($product, 'manufacturer_guarantee', $this->getLocale());
        if ($valueGarantee) {
            $flatProduct['warranty'] = (int)$valueGarantee;
            $flatProduct['warranty_unit'] = 'year';
        }


        for ($i = 1; $i <= 5;$i++) {
            $imageLocale = $this->getAttributeSimple($product, 'image_url_loc_'.$i, $this->getLocale());
            $flatProduct['image_'.$i] =$imageLocale ?: $this->getAttributeSimple($product, 'image_url_'.$i);
        }


        $valueTitle = $this->getAttributeSimple($product, "article_name", $this->getLocale());
        $valueComplementTitle = $this->getAttributeSimple($product, "article_name_additional_information", $this->getLocale());

        $flatProduct['title'] = $valueTitle.$valueComplementTitle;
        
        $descriptionRich = $this->getAttributeSimple($product, 'description_enrichie', $this->getLocale());
        $descriptionSimple = $this->getAttributeSimple($product, 'description', $this->getLocale());
        $descriptionFinal = strlen((string) $descriptionRich) > 5  ? $descriptionRich."<p></p>".$descriptionSimple : $descriptionSimple;
        $flatProduct['description'] = $descriptionFinal ?  $this->removeNewLine($descriptionFinal) : '';

        $fieldsToConvert = [
            "brand" => [
                "field" => "brand",
                "type" => "choice",
            ],
            "manufacturer" => [
                "field" => "brand",
                "type" => "choice",
            ],
            "colour_name" => [
                "field" => "color_generic",
                "type" => "choice",
            ],
            "colour" => [
                "field" => "color_generic",
                "type" => "choice",
            ],
            "battery_life"=>[
                "field" => 'battery_lifetime',
                "type" => "unit",
                "unit" => 'HOUR',
                "convertUnit" => 'hour' ,
                'round' => 0
            ],
            "main_material"=> [
                "field" => "main_material",
                "type" => "choice",
            ],
            "length" => [
                "field" => 'package_lenght',
                "type" => "unit",
                "unit" => 'CENTIMETER',
                "convertUnit" => 'cm' ,
                'round' => 0
            ],
            "width" => [
                "field" => 'package_width',
                "unit" => 'CENTIMETER',
                "type" => "unit",
                "convertUnit" => 'cm' ,
                'round' => 0
            ],
            "height" => [
                "field" => 'package_height',
                "unit" => 'CENTIMETER',
                "type" => "unit",
                "convertUnit" => 'cm',
                'round' => 0
            ],
            "box_length" => [
                "field" => 'package_lenght',
                "type" => "unit",
                "unit" => 'CENTIMETER',
                "convertUnit" => 'cm' ,
                'round' => 0
            ],
            "box_width" => [
                "field" => 'package_width',
                "unit" => 'CENTIMETER',
                "type" => "unit",
                "convertUnit" => 'cm' ,
                'round' => 0
            ],
            "box_height" => [
                "field" => 'package_height',
                "unit" => 'CENTIMETER',
                "type" => "unit",
                "convertUnit" => 'cm',
                'round' => 0
            ],
            "DisplayWeight" => [
                "field" => 'package_weight',
                "unit" => 'KILOGRAM',
                "type" => "unit",
                "convertUnit" => 'kg',
                'round' => 2
            ],
            "weight" => [
                "field" => 'package_weight',
                "unit" => 'KILOGRAM',
                "type" => "unit",
                "convertUnit" => 'kg',
                'round' => 2
            ],
            "power" => [
                "field" => 'power',
                "unit" => 'WATT',
                "type" => "unit",
                "convertUnit" => 'W',
                'round' => 0
            ],


            
         ];

        if($flatProduct['mm_category_id'] ==19952) { // tondeuse
            $flatProduct['coverage']=800;
            $flatProduct['coverage_unit']="m²";
            $flatProduct['working_width_/_diameter']=200;
            $flatProduct['working_width_/_diameter_unit']="m";
        } elseif($flatProduct['mm_category_id'] ==20344) { // chaise
            $flatProduct['style']="Modern";
        } elseif($flatProduct['mm_category_id'] ==20344) { // bold
            $flatProduct['centre-to-centre_distance']=45;
            $flatProduct['centre-to-centre_distance_unit']="mm";
        }

        


        foreach ($fieldsToConvert as $fieldMirakl => $fieldPim) {
            if ($fieldPim['type']=='unit') {
                $valueConverted = $this->getAttributeUnit($product, $fieldPim['field'], $fieldPim['unit'], $fieldPim['round']);
                if($valueConverted) {
                    $flatProduct[$fieldMirakl] = $valueConverted;
                    if ($fieldMirakl !='DisplayWeight') {
                        $flatProduct[$fieldMirakl.'_unit'] = $fieldPim['convertUnit'];
                    }
                }
               
            } elseif ($fieldPim['type']=='choice') {
                $flatProduct[$fieldMirakl] = $this->getAttributeChoice($product, $fieldPim['field'], 'en_GB');
            }
        }


        $country = $this->getAttributeChoice($product, 'country_origin', "en_GB");
        if($country) {
            $flatProduct["origin"] = 'Made in '.$country;
        }


       
        return $flatProduct;
    }
    
   


    


    public function sendProducts(array $products, $header)
    {
        $csv = Writer::createFromString();
        $csv->setDelimiter(';');
        $csv->insertOne($header);
        $this->logger->info("start export ".count($products)." products");
        foreach ($products as $product) {
            $productArray = $this->addProduct($product, $header);
            $csv->insertOne(array_values($productArray));
        }
        $csvContent = $csv->toString();
        $filename = $this->projectDir.'export_products_'.$this->getLowerChannel().'.csv';
        $this->logger->info("start export products locally");

        $fs = new Filesystem();
        $fs->dumpFile($filename, $csvContent);
    }


    private function addProduct(array $product, array $header): array
    {
        $productArray = array_fill_keys($header, '');
        
        foreach ($header as $column) {
            if (array_key_exists($column, $product)) {
                $productArray[$column]=$product[$column];
            }
        }
        return $productArray;
    }
}
