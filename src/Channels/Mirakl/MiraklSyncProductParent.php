<?php

namespace App\Channels\Mirakl;

use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\Channels\Mirakl\MiraklApiParent;
use App\Entity\IntegrationChannel;
use App\Entity\ProductTypeCategorizacion;
use App\Helper\MailService;
use App\Helper\Utils\StringUtils;
use App\Service\Aggregator\ApiAggregator;
use App\Service\Aggregator\ProductSyncParent;
use App\Service\Pim\AkeneoConnector;
use Doctrine\Persistence\ManagerRegistry;
use League\Csv\Writer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

abstract class MiraklSyncProductParent extends ProductSyncParent
{
       
    abstract public function getChannel(): string;

    abstract protected function getLocales(): array;

    abstract protected function getMarketplaceNode(): string;

    protected $projectDir;



    public function __construct(
        ManagerRegistry $manager,
        AkeneoConnector $akeneoConnector,
        LoggerInterface $logger,
        MailService $mailer,
        BusinessCentralAggregator $businessCentralAggregator,
        ApiAggregator $apiAggregator,
        $projectDir
    ) {
        $this->projectDir =  $projectDir.'/public/catalogue/'.$this->getLowerChannel().'/';
        parent::__construct($manager, $logger, $akeneoConnector, $mailer, $businessCentralAggregator, $apiAggregator);
    }



    protected function getProductsEnabledOnChannel()
    {

        $integrationChannel = $this->manager->getRepository(IntegrationChannel::class)->findOneByCode($this->getChannel());
        $saleChannelsCode = [];
        foreach($integrationChannel->getSaleChannels() as $saleChannel) {
            if($saleChannel->getCodePim()) {
                $saleChannelsCode[] = $saleChannel->getCodePim();
            }
        }


        $searchBuilder = new SearchBuilder();
        $searchBuilder
            ->addFilter('brand', 'NOT EMPTY')
            ->addFilter('ean', 'NOT EMPTY')
            ->addFilter('enabled_channel', '=', true, ['scope' => 'Marketplace'])
            ->addFilter('marketplaces_assignement', 'IN', $saleChannelsCode)
            ->addFilter('enabled', '=', true);

        return $this->akeneoConnector->searchProducts($searchBuilder, 'Marketplace');
    }





    protected function getLowerChannel()
    {
        return strtolower($this->getChannel());
    }

    protected function getMiraklApi(): MiraklApiParent
    {
        return $this->getApi();
    }


    protected function doNotExport():array
    {
        return [
            'msrp',
            'msrp_ht',
            'marketplaces_assignement',
            'platform_b2b_websites',
            'selling_rating',
            "image_1"
        ];
    }


    protected function canBeExport($code):bool
    {
        return !in_array($code, $this->doNotExport());
    }


    protected function flatProduct(array $product):array
    {
        $this->logger->info('Flat product '.$product['identifier']);

        $flatProduct = [
            'sku' => $product['identifier'],
        ];
        $productType = $this->getAttributeSimple($product, 'mkp_product_type');
        $categoryCode = $this->getCategoryNode($productType, $this->getMarketplaceNode());

        if($categoryCode) {
            $flatProduct['category_code'] = $categoryCode;
            $category = $this->getCategoryName($categoryCode, $this->getMarketplaceNode());
            if($category) {
                $flatProduct['category_name'] = $category->getLabel();
                $flatProduct['category_path'] = $category->getPath();
            }
        }
        foreach ($product['values'] as $attribute => $value) {
            if($this->canBeExport($attribute)) {
                $attributePim = $this->getAttributeType($attribute);
                foreach ($value as $val) {
                    $nameColumn = $this->getAttributeColumnName($attribute, $val);
                    $data = $val['data'];
            
                    if ($attributePim["type"]=='pim_catalog_simpleselect') {
                        $flatProduct[$nameColumn] = $data;
                        foreach($this->getLocales() as $locale) {
                            $flatProduct[$nameColumn.'-'.$locale] = $this->getTranslationOption($attribute, $data, $locale);
                        }
                    } elseif ($attributePim["type"]=='pim_catalog_multiselect') {
                        $flatProduct[$nameColumn] = implode(',', $data);
                        foreach($this->getLocales() as $locale) {
                            $localeAttributes = [];
                            foreach($data as $dat) {
                                $localeAttributes[]=$this->getTranslationOption($attribute, $dat, $locale);
                            }
                            $flatProduct[$nameColumn.'-'.$locale] =implode(',', $localeAttributes);
                        }
                    } elseif($attributePim["type"]=='pim_catalog_metric') {
                        $flatProduct[$nameColumn] = $data['amount'];
                        $flatProduct[$nameColumn.'-unit'] = $data['unit'];
                        $flatProduct[$nameColumn.'-wunit'] = $data['amount'].' '.$data['unit'];
          
                        if(array_key_exists($nameColumn, $this->getUnitsFormate())&& $data['amount'] >0) {
                            $convert = $this->getUnitsFormate()[$nameColumn];
                            $value =$this->transformUnit($data["unit"], $convert['unit'], $data['amount'], $convert['round']);
                            $flatProduct[$nameColumn.'-formate'] =  $value;
                            $flatProduct[$nameColumn.'-formate-unit'] = $convert['unit'];
                            $flatProduct[$nameColumn.'-formate-wunit'] = $value.' '.$convert['convertUnit'];
                        }
                    } elseif($attributePim["type"]=='pim_catalog_boolean') {
                        $flatProduct[$nameColumn] = (int)$data;
                        foreach($this->getLocales() as $locale) {
                            $flatProduct[$nameColumn.'-'.$locale] = $this->getTranslationBoolean($data, $locale);
                        }
                    } elseif($attributePim["type"]=='pim_catalog_price_collection') {
                        foreach ($data as $subData) {
                            $flatProduct[$nameColumn.'-'.$subData['currency']] = $subData['amount'];
                        }
                    } elseif($attributePim["type"]=='pim_catalog_file') {
                        // do nothing
                    } else {
                        $flatProduct[$nameColumn] = $data;
                    }
                }
            }
            
        }
       
        return $flatProduct;
    }

    


    public function syncProducts()
    {        /** @var  array $products */
        $products = $this->getProductsEnabledOnChannel();
        $productToArrays=[];
        $finalHeader = [];
        foreach ($products as $product) {
            $productToArray = $this->flatProduct($product);
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


   

    protected function sanitizeHtml(string $text): string
    {
        return str_replace(["\r\n", "\n"], '', strip_tags($text));
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
        $filename = $this->projectDir.'export_products_'.$this->getLowerChannel().'_'.date('Ymd_His').'.csv';
        $finalFile = $this->projectDir.'export_products_'.$this->getLowerChannel().'.csv';
        $this->logger->info("start export products locally");

        $fs = new Filesystem();
        $fs->appendToFile($filename, $csvContent);
        $fs->remove($finalFile);
        $fs->appendToFile($finalFile, $csvContent);

        $this->logger->info("start export products on Mirakl");
        $this->getMiraklApi()->sendProductImports($filename);
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


    protected $attributeValues= [];

    protected $attributes= [];

    protected function getAllValuesForAttribute($attributeCode)
    {
        if (!array_key_exists($attributeCode, $this->attributeValues)) {
            $attributesReposne= $this->getMiraklApi()->getAllAttributesValueForCode($attributeCode);
            $this->attributeValues[$attributeCode] = reset($attributesReposne->values_lists);
        }
        return $this->attributeValues[$attributeCode];
    }



    protected function getCodeMarketplace($categoryCode, $attributeCode, $attributeValue)
    {
        $attributes= $this->getAllAttributesForCategory($categoryCode);
        
        foreach ($attributes as $attribute) {
            if (StringUtils::compareString($attribute->code, $attributeCode) && in_array($attribute->type, ['LIST', 'LIST_MULTIPLE_VALUES'])) {
                $valuesAttributes = $this->getAllValuesForAttribute($attribute->values_list);
                foreach ($valuesAttributes->values as $valuesAttribute) {
                    if (StringUtils::compareString($valuesAttribute->label, $attributeValue)) {
                        return $valuesAttribute->code;
                    }
                }
            }
        }
        return null;
    }


    protected function getCodeMarketplaceInList($attributeList, $attributeValue=null)
    {
        if($attributeValue) {
            $valuesAttributes = $this->getAllValuesForAttribute($attributeList);
            foreach ($valuesAttributes->values as $valuesAttribute) {
                if($valuesAttribute->label) {
                    if (StringUtils::compareString($valuesAttribute->label, $attributeValue)) {
                        return $valuesAttribute->code;
                    }
                }
            }
        }
        
        return null;
    }




    

    protected function getAllAttributesForCategory($categoryCode)
    {
        if (!array_key_exists($categoryCode, $this->attributes)) {
            $attributesReposne= $this->getMiraklApi()->getAllAttributesForCategory($categoryCode);
            $this->attributes[$categoryCode] = $attributesReposne->attributes;
        }
        return $this->attributes[$categoryCode];
    }


    protected function getUnitsFormate(): array
    {
        return [
            "product_lenght" => [
                "unit" => 'CENTIMETER',
                "convertUnit" => 'cm' ,
                'round' => 0
            ],
            "product_width" => [
                "unit" => 'CENTIMETER',
                "convertUnit" => 'cm' ,
                'round' => 0
            ],
            "product_height" => [
                "unit" => 'CENTIMETER',
                "convertUnit" => 'cm',
                'round' => 0
            ],
            "product_weight" => [
                "unit" => 'KILOGRAM',
                "convertUnit" => 'kg',
                'round' => 2
            ],
            "package_length" => [
                "unit" => 'CENTIMETER',
                "convertUnit" => 'cm' ,
                'round' => 0
            ],
            "package_width" => [
                "unit" => 'CENTIMETER',
                "convertUnit" => 'cm' ,
                'round' => 0
            ],
            "package_height" => [
                "unit" => 'CENTIMETER',
                "convertUnit" => 'cm',
                'round' => 0
            ],
            "package_weight" => [
                "unit" => 'KILOGRAM',
                "convertUnit" => 'kg',
                'round' => 2
            ],
        ];
    }
}
