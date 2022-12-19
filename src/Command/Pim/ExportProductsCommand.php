<?php

namespace App\Command\Pim;

use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\Service\Pim\AkeneoConnector;
use League\Csv\Writer;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportProductsCommand extends Command
{
    protected static $defaultName = 'app:pim-export-product';
    protected static $defaultDescription = 'Export product from Pim';

    public function __construct(LoggerInterface $logger, AkeneoConnector $akeneoConnector, FilesystemOperator $productStorage)
    {
        $this->akeneoConnector = $akeneoConnector;
        $this->productStorage = $productStorage;
        $this->logger = $logger;
        
        parent::__construct();
    }

    private $akeneoConnector;

    private $logger;

    private $productStorage;


   
    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $products = $this->getProductsEnabledOnChannel();
        $productToArrays=[];
        $base = ['sku', 'categories' ,'enabled' ,'family', 'parent','created','updated'];
        $header = [];
        foreach ($products as $product) {
            $productToArray = $this->flatProduct($product);
            $headerProduct = array_keys($productToArray);
            foreach ($headerProduct as $headerP) {
                if (!in_array($headerP, $header) && !in_array($headerP, $base)) {
                    $header[] = $headerP;
                }
            }
            $productToArrays[]= $productToArray;
        }
        sort($header);
        $finalHeader = array_merge($base, $header);
        $this->sendProducts($productToArrays, $finalHeader);
        return Command::SUCCESS;
    }

    public function flatProduct(array $product):array
    {
        $this->logger->info('Flat product '.$product['identifier']);
        $flatProduct = [
            'sku' => $product['identifier'],
            'categories' => implode(',', $product['categories']),
            'enabled' => (int)$product['enabled'],
            'family' => $product['family'],
            'parent' => $product['parent'],
            'created' => $product['created'],
            'updated' => $product['updated'],
        ];

        foreach ($product['values'] as $attribute => $value) {
            foreach ($value as $val) {
                $nameColumn = $this->getAttributeColumnName($attribute, $val);
                $data = $val['data'];
                if ($this->isMetric($data)) {
                    $flatProduct[$nameColumn] = $data['amount'];
                    $flatProduct[$nameColumn.'-unit'] = $data['unit'];
                } elseif ($this->isCurrency($data)) {
                    foreach ($data as $subData) {
                        $flatProduct[$nameColumn.'-'.$subData['currency']] = $subData['amount'];
                    }
                } elseif (is_array($data)) {
                    $flatProduct[$nameColumn] = implode(',', $data);
                } elseif (is_bool($data)) {
                    $flatProduct[$nameColumn] = (int)$data;
                } else {
                    $flatProduct[$nameColumn] = $data;
                }
            }
        }
        return $flatProduct;
    }


    public function isMetric($val)
    {
        return is_array($val) && array_key_exists("unit", $val);
    }

    public function isCurrency($val)
    {
        return is_array($val) && is_array($val[0]);
    }


    public function getAttributeColumnName($attribute, $val)
    {
        $nameAttribute=$attribute;
        if ($val['locale']) {
            $nameAttribute .= '-'. $val['locale'];
        }
        if ($val['scope']) {
            $nameAttribute .= '-'. $val['scope'];
        }
        return $nameAttribute;
    }



    public function getProductsEnabledOnChannel()
    {
        $searchBuilder = new SearchBuilder();
        $searchBuilder
            ->addFilter('enabled_channel', '=', true, ['scope' => 'Marketplace'])
            ->addFilter('enabled', '=', true);

        return $this->akeneoConnector->searchProducts($searchBuilder, 'Marketplace');
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
        $this->logger->info("start export products locally");
        $this->productStorage->write('products/export_products_sftp.csv', $csvContent);
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
