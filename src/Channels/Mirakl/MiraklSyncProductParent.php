<?php

namespace App\Channels\Mirakl;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\Channels\Mirakl\MiraklApiParent;
use App\Helper\MailService;
use App\Service\Aggregator\ApiAggregator;
use App\Service\Aggregator\ProductSyncParent;
use App\Service\Pim\AkeneoConnector;
use League\Csv\Writer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

abstract class MiraklSyncProductParent extends ProductSyncParent
{
    abstract protected function getProductsEnabledOnChannel();
    
    abstract public function getChannel(): string;

    abstract protected function flatProduct(array $product): array;

    protected $projectDir;


    public function __construct(
        AkeneoConnector $akeneoConnector,
        LoggerInterface $logger,
        MailService $mailer,
        BusinessCentralAggregator $businessCentralAggregator,
        ApiAggregator $apiAggregator,
        $projectDir
    ) {
        $this->projectDir =  $projectDir.'/var/export/'.$this->getLowerChannel().'/';
        parent::__construct($logger, $akeneoConnector, $mailer, $businessCentralAggregator, $apiAggregator);
    }

    protected function getLowerChannel()
    {
        return strtolower($this->getChannel());
    }

    protected function getMiraklApi(): MiraklApiParent
    {
        return $this->getApi();
    }


    public function syncProducts()
    {
        /** @var  array $products */
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
        //sort($finalHeader);
        $this->sendProducts($productToArrays, $finalHeader);
    }


   
    protected function convertHtmlToMarkdown(string $text): string
    {
        $conversion = [
            "<li>" => '+ ',
            "</li>" => '',
            "<ul>" => "",
            "</ul>" => "",
            "<ol>" => "",
            "</ol>" => "",
            "<p>" => "",
            "</p>" => "\\",
            "<br/>" => "\\",
            "<b>" => '**',
            "</b>" => '**',
            "<strong>" => '**',
            "</strong>" => '**',
            "<i>" => '*',
            "</i>" => '*',
            "<em>" => '*',
            "</em>" => '*',
            
        ];
       $strReplace = str_replace(array_keys($conversion), array_values($conversion), $text);
       return $this->sanitizeHtml($strReplace);    
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
        $this->logger->info("start export products locally");

        $fs = new Filesystem();
        $fs->appendToFile($filename, $csvContent);
        $this->logger->info("start export products on Mirakl");
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
