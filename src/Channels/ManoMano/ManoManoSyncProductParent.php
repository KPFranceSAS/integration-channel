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
            'merchant_category' => $this->getFamilyName($product['family'], $this->getLocale()),
            'product_url' => "",
            'Sample_SKU' => 0,
            'Unit_count' => 1,
            "unit_count_type" => 'pièce',
            "shipping_time" => "3#7",
            "carrier" => "UPS",
            "shipping_price_vat_inc" => 0,
            "use_grid" => 0,
        ];

        $valueGarantee =  $this->getAttributeChoice($product, 'manufacturer_guarantee', $this->getLocale());
        if ($valueGarantee) {
            $flatProduct['ManufacturerWarrantyTime'] = (int)$valueGarantee;
        }

        //mm_category_id

        for ($i = 1; $i <= 5;$i++) {
            $flatProduct['image_'.$i] = $this->getAttributeSimple($product, 'image_url_'.$i);
        }


       
        $localizablesTextFields= [
            'title' => 'article_name',
            'description' => 'description',
            'brand' => 'brand',
            'manufacturer' => 'brand',
           
        ];
        


        foreach ($localizablesTextFields as $localizableMirakl => $localizablePim) {
            $value = $this->getAttributeSimple($product, $localizablePim, $this->getLocale());
            if ($value) {
                if ($localizableMirakl == 'description') {
                    $value = $this->removeNewLine($value);
                } else {
                    $value = $this->sanitizeHtml($value);
                }
            }

            $flatProduct[$localizableMirakl] = $value;
        }


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
                "field" => 'product_lenght',
                "type" => "unit",
                "unit" => 'CENTIMETER',
                "convertUnit" => 'cm' ,
                'round' => 0
            ],
            "width" => [
                "field" => 'product_width',
                "unit" => 'CENTIMETER',
                "type" => "unit",
                "convertUnit" => 'cm' ,
                'round' => 0
            ],
            "height" => [
                "field" => 'product_height',
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
            $value = null;
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
