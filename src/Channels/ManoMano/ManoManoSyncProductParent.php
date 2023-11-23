<?php

namespace App\Channels\ManoMano;

use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\Entity\IntegrationChannel;
use App\Entity\Product;
use App\Entity\SaleChannel;
use App\Helper\MailService;
use App\Service\Aggregator\ApiAggregator;
use App\Service\Aggregator\PriceStockAggregator;
use App\Service\Aggregator\ProductSyncParent;
use App\Service\Carriers\UpsGetTracking;
use App\Service\Pim\AkeneoConnector;
use Doctrine\Persistence\ManagerRegistry;
use League\Csv\Writer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

abstract class ManoManoSyncProductParent extends ProductSyncParent
{
    abstract public function getChannel(): string;

    abstract public function getChannelPim(): string;

    abstract protected function getLocale(): string;

    protected $priceStockAggregator;

    protected $projectDir;


    public function __construct(
        AkeneoConnector $akeneoConnector,
        LoggerInterface $logger,
        MailService $mailer,
        BusinessCentralAggregator $businessCentralAggregator,
        ApiAggregator $apiAggregator,
        ManagerRegistry $manager,
        PriceStockAggregator $priceStockAggregator,
        $projectDir
    ) {
        $this->projectDir =  $projectDir.'/public/manomano/catalogue/';
        $this->priceStockAggregator = $priceStockAggregator;
        $this->manager = $manager;
        parent::__construct($logger, $akeneoConnector, $mailer, $businessCentralAggregator, $apiAggregator);
    }

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
            'mm_category_id' => null,
            'product_url' => "",
            'min_quantity' => "",
            'Sample_SKU' => "",
            'Unit_count' => "",
            "unit_count_type" => '',
        ];

        $equivalences = [
            "marketplace_solar_panel_energy_travel"	=>21255,
            "marketplace_solar_panel_mobile"	=>21255,
            "marketplace_generator_energy_travel"	=>22185,
            "marketplace_garden_spa_home"	=>20008,
            "markerplace_blender"	=>20597,
            "marketplace_air_fryer"	=>20562,
            "marketplace_smart_lock"	=>21503,
            "marketplace_smart_lock_accesories"=>	21503,
            "marketplace_travel_oven"	=>19639,
            "marketplace_pizza_peel"	=>19639,
            "marketplace_pizza_cutter"=>	19639,
            "marketplace_pizza_brush"	=>19639,
            "marketplace_pizza_scale"	=>19639,
            "marketplace_pizza_roller"=>	19639,
            "marketplace_pizza_apparel"	=>19639,
            "marketplace_pizza_stone"	=>19639,
            "marketplace_pizza_cooker"	=>19639,
            "marketplace_pizza_table"	=>19639,
            "marketplace_pizza_other"=>19639
        ];






        foreach($equivalences as $pimCategory => $mmCategory) {
            if(in_array($pimCategory, $product['categories'])) {
                $flatProduct['mm_category_id'] = $mmCategory;
                $flatProduct['merchant_category'] = $this->getCategorieName($pimCategory, $this->getLocale());
                break;
            }
        }
        


        $valueGarantee =  $this->getAttributeChoice($product, 'manufacturer_guarantee', $this->getLocale());
        if ($valueGarantee) {
            $flatProduct['ManufacturerWarrantyTime'] = (int)$valueGarantee;
        }


        for ($i = 1; $i <= 5;$i++) {
            $imageLocale = $this->getAttributeSimple($product, 'image_url_loc_'.$i, $this->getLocale());
            $flatProduct['image_'.$i] =$imageLocale ? $imageLocale : $this->getAttributeSimple($product, 'image_url_'.$i);
        }


        $valueTitle = $this->getAttributeSimple($product, "article_name", $this->getLocale());
        $valueComplementTitle = $this->getAttributeSimple($product, "article_name_additional_information", $this->getLocale());

        $flatProduct['title'] = $valueTitle.$valueComplementTitle;
        
        $descriptionRich = $this->getAttributeSimple($product, 'description_enrichie', $this->getLocale());
        $descriptionSimple = $this->getAttributeSimple($product, 'description', $this->getLocale());
        $descriptionFinal = strlen($descriptionRich) > 5  ? $descriptionRich."<p></p>".$descriptionSimple : $descriptionSimple;
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
            "DisplayWeight" => [
                "field" => 'package_weight',
                "unit" => 'KILOGRAM',
                "type" => "unit",
                "convertUnit" => 'kg',
                'round' => 2
            ],
            "weight" => [
                "field" => 'product_weight',
                "unit" => 'KILOGRAM',
                "type" => "unit",
                "convertUnit" => 'kg',
                'round' => 2
            ],
            
         ];

        foreach ($fieldsToConvert as $fieldMirakl => $fieldPim) {
            if ($fieldPim['type']=='unit') {
                $valueConverted = $this->getAttributeUnit($product, $fieldPim['field'], $fieldPim['unit'], $fieldPim['round']);
                $flatProduct[$fieldMirakl] = $valueConverted;
                if ($fieldMirakl !='DisplayWeight') {
                    $flatProduct[$fieldMirakl.'_unit'] = $fieldPim['convertUnit'];
                }
            } elseif ($fieldPim['type']=='choice') {
                $flatProduct[$fieldMirakl] = $this->getAttributeChoice($product, $fieldPim['field'], $this->getLocale());
            }
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
