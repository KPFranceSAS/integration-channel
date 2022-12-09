<?php

namespace App\Service\Carriers;

use Exception;
use GuzzleHttp\Client;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;

class DhlGetTracking
{
    protected $logger;

    protected $dhlStorage;

    protected $trackings;

    protected $client;


    public function __construct(FilesystemOperator $dhlStorage, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->dhlStorage = $dhlStorage;
    }


    public function getTrackingExternal($externalOrderNumber): ?array
    {
        if (!$this->trackings) {
            $this->initializeTrackings();
        }
        return (array_key_exists($externalOrderNumber, $this->trackings))
            ? $this->trackings[$externalOrderNumber]
            : null;
    }


    public function getTrackingExternalWeb($externalOrderNumber): ?string
    {
        try {
            $client = new Client();
            $response = $client->get(
                'https://clientesparcel.dhl.es/LiveTracking/api/expediciones?numeroExpedicion=' . $externalOrderNumber,
                ['connect_timeout' => 1]
            );
            $body = json_decode((string) $response->getBody(), true);
            if ($body) {
                return str_replace(" 20", "", $body['NumeroExpedicionTLG']);
            }
        } catch (Exception $e) {
            $this->logger->alert('DHL is not accessible');
        }

        return null;
    }


    public function isDeliveredExternalNumber($externalOrderNumber): bool
    {
        try {
            $client = new Client();
            $response = $client->get(
                'https://clientesparcel.dhl.es/LiveTracking/api/expediciones?numeroExpedicion=' . $externalOrderNumber,
                ['connect_timeout' => 1]
            );
            $body = json_decode((string) $response->getBody(), true);
            if ($body) {
                if ($body['FechaEntrega']) {
                    return true;
                }
            }
        } catch (Exception $e) {
            $this->logger->alert('DHL is not accessible');
        }

        return false;
    }










    public function initializeTrackings()
    {
        $this->logger->info("Get all the datas file");
        $this->trackings = [];

        $files = $this->getAllFiles();
        $this->extraContentFromFiles($files);
        $this->logger->info('Nb lines of trackings ' . count($this->trackings));
        return $this->trackings;
    }


    private function extraContentFromFiles($files)
    {
        foreach ($files as $file) {
            $lines = $this->extraContentFromFile($file);
            foreach ($lines as $line) {
                if ($line["recordType"] == 'Delivery') {
                    if (!array_key_exists($line['customerReference'], $this->trackings)) {
                        $this->trackings[$line['customerReference']] = [
                            'lines' => [],
                            'tracking' => $line['dhlTrackingNumber'],
                            'urlTracking' => $this->getTrackingUrlBase($line['dhlTrackingNumber']),
                        ];
                    }
                    $this->trackings[$line['customerReference']]['lines'][] = $line;
                }
            }
        }
    }



    private function extraContentFromFile($file)
    {
        $linesFormatted = [];
        $contentFile = $this->dhlStorage->read($file);
        $lines = explode("\r\n", $contentFile);
        foreach ($lines as $line) {
            if (strlen($line) > 100) {
                $linesFormatted[] = $this->transformLineInArray($line);
            }
        }
        return $linesFormatted;
    }



    private function getAllFiles()
    {
        $filesToIntegrate = [];
        $directories = ["proc", "build"];
        foreach ($directories as $directory) {
            $filesProcessed = $this->dhlStorage->listContents("/" . $directory);
            foreach ($filesProcessed as $fileProcessed) {
                $filesToIntegrate[] = $fileProcessed->path();
            }
        }
        return $filesToIntegrate;
    }



    private function getTrackingUrlBase($codeTracking)
    {
        return "https://clientesparcel.dhl.es/LiveTracking/ModificarEnvio/" . $codeTracking;
    }



    /**
    * Description fichier
    * Campo	        Lon. Des. Has.
    * Customer Code	6	1	6	0		Client Code
    * Product type	3	7	9			DHL Product type  (By default 800=Europlus)
    * Origin depot	3	10	12			Shipment Origin Depot       (Example 28 =Madrid)
    * Cust-Reference	35	13	47			Reference or Key used by the customer on the shipment
    * DHL Tracking Number	15	48	62			DHL Tracking Number (For Europlus Orig(2)+ShipmNbr(7)+Corr(1) left adjusted)
    * Record Type	2	63	64	 		01 =  Delivery  02 = Incidents  03=Text lines
    * Status code	2	65	66			Delivery code or Incident code (see tables below)
    * Date	8	67	74	0		YYYYMMDD Status date
    * Time	6	75	80	0		Delivery time or Incident time (HHMMSS)
    * Identicket	10	81	90			Identicket (Only for Big Stores)
    * Remarks Text	50	91	140			Text lines (Up to 3 text possible lines by incident code) record type=03
    * Generation Point	3	141	143
    * Event	3	144	146
    * Reason	3	147	149
     */
    private function transformLineInArray($line)
    {
        $lineArray = [
            "customerCode" => substr($line, 0, 6),
            "productType" => substr($line, 6, 3),
            "originDepot" => substr($line, 9, 3),
            "customerReference" => trim(substr($line, 12, 35)),
            "dhlTrackingNumber" => trim(substr($line, 47, 15)),
            "recordType" => $this->getRecordType(substr($line, 62, 2)),
            "statusCode" => $this->getStatusCode(substr($line, 64, 2)),
            "date" => \DateTime::createFromFormat("YmdHis", substr($line, 66, 14)),
        ];
        return $lineArray;
    }


    private function getRecordType($recordType)
    {
        $correpondingRecordType = [
            "01" => "Delivery",
            "02" => "Incidents",
            "03" => "Text lines",
        ];

        return array_key_exists($recordType, $correpondingRecordType) ? $correpondingRecordType[$recordType] : 'UNKNOWN recordType ' . $recordType;
    }


    private function getStatusCode($statusCode)
    {
        $statusCodeType = [
            "AD" => "CUSTOMS CLEARANCE",
            "AI" => "INCORRECTDELIVERY TRUCK",
            "AV" => "DAMAGE",
            "CE" => "HELD, PENDING APPOINTMENT",
            "CO" => "COMMENTS  (NOT INCIDENT CODE)",
            "CH" => "OUT OF DELIVERY TIME",
            "CR" => "CONSIGNEE CLOSED, NO ONE IN",
            "CV" => "ON HOLYDAY",
            "DD" => "DELIVERED DAMAGGED",
            "DE" => "DESTROYED",
            "DI" => "INCORRECT ADDRESS",
            "DS" => "UNKNOWN ADDRESS",
            "DV" => "UNKNOWN ADDRESS, RETURN BACK",
            "EC" => "DELIVER ON AGREED DATE",
            "EE" => "LABELLING ERROR",
            "EI" => "UNCOMPLETED DELIVERED",
            "FA" => "FREIGHT LOST",
            "FM" => "OTHER REASONS (FORCE MAJOR), STRIKE",
            "GD" => "DESTINATION ARRANGEMENTS",
            "GE" => "DELIVER ON AGREED DATE",
            "GO" => "ORIGIN ARRANGEMENTS BY (DS, MR, CR, DI, AV ….. previous, )",
            "GS" => "DELIVER ON AGREED DATE, HYPERMARKETS ONLY",
            "MC" => "FREIGHT NOT LOADED IN DELIVERY TRUCK",
            "MR" => "FREIGHT REJECTED BY CONSIGNEE",
            "NA" => "NOT ON DELIVERY TRUCK",
            "NL" => "FREIGHT NOT ARRIVED",
            "OH" => "ON HOLD  - BY CUSTOMER REQUEST",
            "PE" => "SHIPMENT TEMPORARY UNLOCATED",
            "PD" => "AWAITING DOCUMENTS (NORMALLY FOR CANARIES ISLANDS, ANDORRA, GIBRALTAR)",
            "PR" => "DELIVER WITH PROVISORY DELIVERY NOTE",
            "PP" => "MISSROUTED",
            "RA" => "CONSIGNEE WILL COLLECT ON DEPOT",
            "RC" => "RELOADED (Afternoon delivery)",
            "RO" => "STOLEN",
            "RT" => "RETURNED TO SENDER",
            "SD" => "DOCUMENTS SOLICITATED",
            "ID" => "IDENTICKET NUMBER FOR HYPERMARKETS  (NO INCIDENT)"
        ];

        return array_key_exists($statusCode, $statusCodeType) ? $statusCodeType[$statusCode] : 'UNKNOWN CODE ' . $statusCode;
    }
}
