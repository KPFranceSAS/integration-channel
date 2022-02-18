<?php

namespace App\Command\AliExpress;

use App\Entity\WebOrder;
use App\Service\AliExpress\AliExpressApi;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StockCalculCommand extends Command
{
    protected static $defaultName = 'app:aliexpress-build-stock';
    protected static $defaultDescription = 'CheckStock Aliexpress';





    public function __construct(FilesystemOperator $awsStorage, AliExpressApi $aliExpressApi, LoggerInterface $logger)
    {
        parent::__construct();
        $this->aliExpressApi = $aliExpressApi;
        $this->logger = $logger;
        $this->awsStorage = $awsStorage;
    }

    private $awsStorage;

    private $aliExpressApi;

    private $logger;

    private $notFOund;




    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initializeStockLevels();
        $this->notFOund = [];
        $stocks = $this->sendStocks();
        $this->createExport($stocks);
        $notfounds = array_unique($this->notFOund);
        foreach ($notfounds as $notFound) {
            $output->writeln('Not found ' . $notFound);
        }
        return Command::SUCCESS;
    }



    private function createExport($stocks)
    {
        $writer = WriterEntityFactory::createCSVWriter();
        $writer->openToFile('/home/esteban/projets/integration-channel/var/file/aliexpress.csv');

        $cellHeaders = [];
        foreach ($this->getHeader() as $field) {
            $cellHeaders[] = WriterEntityFactory::createCell($field);
        }
        $singleRow = WriterEntityFactory::createRow($cellHeaders);
        $writer->addRow($singleRow);
        foreach ($stocks as $stock) {

            $cellDatas = [];
            foreach ($stock as $stockValue) {
                $cellDatas[] = WriterEntityFactory::createCell($stockValue);
            }
            $singleRowData = WriterEntityFactory::createRow($cellDatas);
            $writer->addRow($singleRowData);
        }
        $writer->close();
    }




    /**
     * process all invocies directory
     *
     * @return void
     */
    public function sendStocks()
    {

        $stocks = [];
        $products = $this->aliExpressApi->getAllActiveProducts();
        foreach ($products as $product) {
            $stocks[] = $this->sendStock($product);
        }

        return $stocks;
    }


    public function sendStock($product)
    {

        $this->logger->info('Send stock for ' . $product->subject . ' / Id ' . $product->product_id);

        $productInfo = $this->aliExpressApi->getProductInfo($product->product_id);
        $skuCode = $this->extractSkuFromResponse($productInfo);
        $brand = $this->extractBrandFromResponse($productInfo);
        $stockSkuAe = $this->extractStockFromResponse($productInfo);
        $stockTocHeck = $this->defineStockBrand($brand);

        $stock = [
            'SKU' => $skuCode,
            'ALIEXPRESSID' => $product->product_id,
            'BRAND' => $brand,
            'NAME' => $product->subject,
            'CENTRAL' => $this->getStockProductWarehouse($skuCode, WebOrder::DEPOT_CENTRAL),
            'MADRID' => $this->getStockProductWarehouse($skuCode, WebOrder::DEPOT_MADRID),
            'ALIEXPRESS' => $stockSkuAe,
            'STOCKSEND' => 0,
            'STOCKSENDBUFFER' => 0,
            'WILLBESENDBY' => $stockTocHeck,
        ];

        $stock['STOCKSEND'] = $stock[$stockTocHeck];
        $stock['STOCKSENDBUFFER'] = $this->getStockToSend($stock['STOCKSEND']);


        return $stock;
    }


    private function getHeader()
    {
        return [
            'SKU',
            'BRAND',
            'NAME',
            'CENTRAL',
            'MADRID',
            'ALIEXPRESS',
            'STOCKSEND',
            'STOCKSENDBUFFER',
            'WILLBESENDBY'
        ];
    }



    public function defineStockBrand($brand)
    {
        if ($brand && in_array($brand, ['ECOFLOW', 'AUTELROBOTICS'])) {
            return WebOrder::DEPOT_MADRID;
        }
        return WebOrder::DEPOT_CENTRAL;
    }


    private function cleanString(string $string)
    {
        return strtoupper(trim(str_replace(' ', '', $string)));
    }


    private function checkIfEgalString(string $string1, string $string2)
    {
        return $this->cleanString($string1) == $this->cleanString($string2);
    }

    private function extractBrandFromResponse($productInfo)
    {
        foreach ($productInfo->aeop_ae_product_propertys->global_aeop_ae_product_property as $property) {
            if ($this->checkIfEgalString($property->attr_name, 'Brand Name')) {
                return $this->cleanString($property->attr_value);
            }
        }
        return null;
    }



    private function extractSkuFromResponse($productInfo)
    {
        $skuList = reset($productInfo->aeop_ae_product_s_k_us->global_aeop_ae_product_sku);
        return $skuList->sku_code;
    }


    private function extractStockFromResponse($productInfo)
    {
        $skuList = reset($productInfo->aeop_ae_product_s_k_us->global_aeop_ae_product_sku);
        return $skuList->ipm_sku_stock;
    }




    protected function getStockToSend($stock): int
    {
        return round(0.7 * $stock, 0, PHP_ROUND_HALF_DOWN);
    }




    protected function getStockProductWarehouse($sku, $depot = WebOrder::DEPOT_CENTRAL): int
    {
        $key = $sku . '_' . $depot;
        if (array_key_exists($key, $this->stockLevels)) {
            return $this->stockLevels[$key];
        } else {
            $this->notFOund[] = $sku;
        }
        return 0;
    }




    private function initializeStockLevels()
    {

        $this->stockLevels = [];
        $contentFile = $this->awsStorage->readStream('stock/StockMarketplaces.csv');

        $toRemove = fgetcsv($contentFile, null, ';');
        dump($toRemove);
        $header = fgetcsv($contentFile, null, ';');
        dump($header);
        while (($values = fgetcsv($contentFile, null, ';')) !== false) {
            if (count($values) == count($header)) {
                $stock = array_combine($header, $values);
                $key = $stock['SKU'] . '_' . $stock['LocationCode'];
                $this->stockLevels[$key] = (int)$stock['AvailableQty'];
            }
        }

        dump($this->stockLevels);

        $this->logger->info('Nb of lines :' . count($this->stockLevels));
        return $this->stockLevels;
    }
}
