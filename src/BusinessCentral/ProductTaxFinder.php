<?php

namespace App\BusinessCentral;


use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\BusinessCentral\Connector\BusinessCentralConnector;
use Exception;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;

class ProductTaxFinder
{
    protected $logger;
    protected $businessCentralAggregator;
    protected $awsStorage;
    protected $canonDigitals;

    public function __construct(
        FilesystemOperator $awsStorage,
        LoggerInterface $logger,
        BusinessCentralAggregator $businessCentralAggregator
    ) {
        $this->logger = $logger;
        $this->businessCentralAggregator = $businessCentralAggregator;
        $this->awsStorage = $awsStorage;
    }


    public function getCanonDigitalForItem(string $itemId, string $company): float
    {
        if (!$this->canonDigitals) {
            $this->initializeCanonDigitals();
        }

        $item = $this->getBusinessCentralConnector($company)->getItem($itemId);
        if ($item) {
            $sku = $item['number'];
            if (array_key_exists($sku, $this->canonDigitals)) {
                $this->logger->info('Canon digital de ' . $this->canonDigitals[$sku]['UnitPriceDigitalCanon'] . ' for ' . $sku);
                $value = floatval(str_replace(',', '.', $this->canonDigitals[$sku]['UnitPriceDigitalCanon']));
                return $value;
            } else {
                $this->logger->info('No canon digital for ' . $sku);
            }
        } else {
            $this->logger->error('No item found with Id ' . $itemId . ' in the company ' . $company);
        }

        return 0;
    }

    public function getBusinessCentralConnector($companyName): BusinessCentralConnector
    {
        return $this->businessCentralAggregator->getBusinessCentralConnector($companyName);
    }


    public function initializeCanonDigitals()
    {
        $this->logger->info('Get the file tracking/SKUDigitalCanon.csv');
        $this->canonDigitals = [];
        $contentFile = $this->awsStorage->readStream('tracking/SKUDigitalCanon.csv');
        $header = fgetcsv($contentFile, null, ';');
        while (($values = fgetcsv($contentFile, null, ';')) !== false) {
            if (count($values) == count($header)) {
                $canonDigital = array_combine($header, $values);
                $this->canonDigitals[$canonDigital['SKU']] = $canonDigital;
            }
        }
        if (count($this->canonDigitals) == 0) {
            throw new Exception('Error of mapping for canon products files published ' . json_encode($header));
        }

        $this->logger->info('Nb of lines :' . count($this->canonDigitals));
        return $this->canonDigitals;
    }
}
