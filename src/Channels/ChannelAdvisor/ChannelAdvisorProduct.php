<?php

namespace App\Channels\ChannelAdvisor;

use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\Entity\IntegrationChannel;
use App\Helper\MailService;
use App\Service\Aggregator\ApiAggregator;
use App\Service\Aggregator\ProductSyncParent;
use App\Service\Pim\AkeneoConnector;
use League\Csv\Writer;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;

/**
 * Services that will get through the API the order from ChannelAdvisor
 *
 */
class ChannelAdvisorProduct extends ProductSyncParent
{
    protected $defaultStorage;
    protected $channelAdvisorStorage;
    
    public function __construct(
        AkeneoConnector $akeneoConnector,
        LoggerInterface $logger,
        MailService $mailer,
        FilesystemOperator $defaultStorage,
        FilesystemOperator $channelAdvisorStorage,
        BusinessCentralAggregator $businessCentralAggregator,
        ApiAggregator $apiAggregator
    ) {
        $this->defaultStorage = $defaultStorage;
        $this->channelAdvisorStorage = $channelAdvisorStorage;
        parent::__construct($logger, $akeneoConnector, $mailer, $businessCentralAggregator, $apiAggregator);
    }


    public function syncProducts()
    {
        /** @var  array $products */
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
    }



    public function getChannel(): string
    {
        return  IntegrationChannel::CHANNEL_CHANNELADVISOR;
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
        $filename = 'export_products_sftp_'.date('Ymd_His').'.csv';
        $this->logger->info("start export products locally");
        $this->defaultStorage->write('products/'.$filename, $csvContent);
        $this->logger->info("start export products on channeladvisor");
        $this->channelAdvisorStorage->write('/accounts/12044693/Products/'.$filename, $csvContent);
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
