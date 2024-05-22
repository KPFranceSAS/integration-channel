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
use Mirakl\MCI\Shop\Request\Product\ProductImportRequest;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

abstract class MiraklSyncProductParent extends ProductSyncParent
{
       
    abstract public function getChannel(): string;

    abstract protected function flatProduct(array $product): array;

    protected $projectDir;

    protected $manager;

    protected $productTypes=[];


    public function __construct(
        ManagerRegistry $manager,
        AkeneoConnector $akeneoConnector,
        LoggerInterface $logger,
        MailService $mailer,
        BusinessCentralAggregator $businessCentralAggregator,
        ApiAggregator $apiAggregator,
        $projectDir
    ) {
        $this->manager = $manager->getManager();
        $this->projectDir =  $projectDir.'/var/catalogue/'.$this->getLowerChannel().'/';
        parent::__construct($logger, $akeneoConnector, $mailer, $businessCentralAggregator, $apiAggregator);
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




    private function initializeCategories()
    {
        $productCategorizations = $this->manager->getRepository(ProductTypeCategorizacion::class)->findAll();
        foreach($productCategorizations as $productCategorization) {
            $this->productTypes[$productCategorization->getPimProductType()]=$productCategorization;
        }

    }

    public function getCategoryNode($productType, $marketplace)
    {
        if(!$this->productTypes) {
            $this->initializeCategories();
        }
        if(is_null($productType)) {
            return null;
        }

        if(!array_key_exists($productType, $this->productTypes)) {
            return '';
        }

        $productTypeCat = $this->productTypes[$productType]->{'get'.ucfirst($marketplace).'Category'}();

        if($productTypeCat && strlen($productTypeCat)> 0) {
            return $productTypeCat;
        } else {
            return null;
        }

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
        $filename = $this->projectDir.'export_products/export_products_'.$this->getLowerChannel().'_'.date('Ymd_His').'.csv';
        $this->logger->info("start export products locally");

        $fs = new Filesystem();
        $fs->appendToFile($filename, $csvContent);
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
}
