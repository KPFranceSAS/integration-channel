<?php

namespace App\Service\Carriers;


use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;



class GetTracking
{


    protected $logger;

    protected $awsStorage;

    protected $trackings;


    public function __construct(FilesystemOperator $awsStorage, LoggerInterface $logger, DhlGetTracking $dhlGetTracking)
    {
        $this->logger = $logger;
        $this->awsStorage = $awsStorage;
        $this->dhlGetTracking = $dhlGetTracking;
    }




    public function getTracking(string $company, string $invoiceNumber): ?array
    {
        $this->importFiles($company);
        return array_key_exists($invoiceNumber, $this->trackings[$company]) ? $this->trackings[$company][$invoiceNumber] : null;
    }



    private function importFiles($company)
    {
        if (!$this->trackings || ($this->trackings && !array_key_exists($company, $this->trackings))) {
            if (!$this->trackings) {
                $this->trackings = [];
            }
            $this->trackings[$company] = $this->importFile($company);
        }
    }

    private function importFile($company)
    {
        $filename = $company . 'Invoiced Order.csv';
        $this->logger->info('Get the file ' . $filename);
        $trackings = [];
        $contentFile = $this->awsStorage->readStream('tracking/' . $filename);
        $header = fgetcsv($contentFile, null, ';');
        while (($values = fgetcsv($contentFile, null, ';')) !== false) {
            if (count($values) == count($header)) {
                $tracking = array_combine($header, $values);
                $trackings[$tracking['Invoice number']] = $tracking;
            }
        }
        if (count($trackings) == 0) {
            throw new \Exception('Error of mapping for tracking  files for company  ' . $company . " >> " . json_encode($header));
        }

        $this->logger->info('Nb of lines :' . count($trackings));
        return $trackings;
    }



    public function getDhlTracking($externalNumber): ?string
    {
        $this->logger->info('Check on DHL API :' . $externalNumber);
        return $this->dhlGetTracking->getTrackingExternalWeb($externalNumber);
    }
}
