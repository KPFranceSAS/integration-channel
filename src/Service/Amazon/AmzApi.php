<?php

namespace App\Service\Amazon;

use AmazonPHP\SellingPartner\Configuration;
use AmazonPHP\SellingPartner\Marketplace;
use AmazonPHP\SellingPartner\Model\FulfillmentInbound\CreateInboundShipmentPlanRequest;
use AmazonPHP\SellingPartner\Model\FulfillmentInbound\InboundShipmentRequest;
use AmazonPHP\SellingPartner\Model\Reports\CreateReportSpecification;
use AmazonPHP\SellingPartner\Regions;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use AmazonPHP\SellingPartner\STSClient;
use Buzz\Client\Curl;
use DateInterval;
use DateTime;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Log\LoggerInterface;

class AmzApi
{
    final public const TYPE_REPORT_LAST_UPDATE_ORDERS = 'GET_FLAT_FILE_ALL_ORDERS_DATA_BY_LAST_UPDATE_GENERAL';
    final public const TYPE_REPORT_LAST_UPDATE_ARCHIVED_ORDERS = 'GET_FLAT_FILE_ARCHIVED_ORDERS_DATA_BY_ORDER_DATE';
    final public const TYPE_REPORT_LISTINGS_ALL_DATA = 'GET_MERCHANT_LISTINGS_ALL_DATA';
    final public const TYPE_REPORT_OPEN_LISTINGS_DATA = 'GET_FLAT_FILE_OPEN_LISTINGS_DATA';
    final public const TYPE_REPORT_RETURNS_DATA = 'GET_FBA_FULFILLMENT_CUSTOMER_RETURNS_DATA';
    final public const TYPE_REPORT_INVENTORY_DATA_BY_COUNTRY = 'GET_AFN_INVENTORY_DATA_BY_COUNTRY';
    final public const TYPE_REPORT_INVENTORY_DATA = 'GET_AFN_INVENTORY_DATA';
    final public const TYPE_REPORT_MANAGE_INVENTORY = 'GET_FBA_MYI_UNSUPPRESSED_INVENTORY_DATA';
    final public const TYPE_REPORT_RESTOCK_INVENTORY = 'GET_RESTOCK_INVENTORY_RECOMMENDATIONS_REPORT';
    final public const TYPE_REPORT_REIMBURSEMENT = 'GET_FBA_REIMBURSEMENTS_DATA';
    final public const TYPE_REPORT_MANAGE_INVENTORY_ARCHIVED = 'GET_FBA_MYI_ALL_INVENTORY_DATA';
    final public const TYPE_REPORT_REMOVAL_SHIPMENT_DETAIL = 'GET_FBA_FULFILLMENT_REMOVAL_SHIPMENT_DETAIL_DATA';
    final public const TYPE_REPORT_REMOVAL_ORDER_DETAIL = 'GET_FBA_FULFILLMENT_REMOVAL_ORDER_DETAIL_DATA';


    final public const STATUS_REPORT_DONE = 'DONE';
    final public const STATUS_REPORT_CANCELLED = 'CANCELLED';
    final public const STATUS_REPORT_FATAL = 'FATAL';
    final public const STATUS_REPORT_IN_PROGRESS = 'IN_PROGRESS';
    final public const STATUS_REPORT_IN_QUEUE = 'IN_QUEUE';

    private $dateInitialisationToken;

    private $sdk;

    private $accessToken;

    public function __construct(private readonly LoggerInterface $logger, private readonly string $amzLwaId, private readonly string $amzLwaSecret, private readonly string $amzAwsId, private readonly string $amzAwsSecret, private readonly string $amzArn, private readonly string $amzRefreshToken, private readonly string $amzSellerId)
    {
        $factory = new Psr17Factory();
        $client = new Curl($factory);

        $sts = new STSClient(
            $client,
            $factory,
            $factory
        );

        $configuration =  Configuration::forIAMRole(
            $this->amzLwaId,
            $this->amzLwaSecret,
            $sts->assumeRole(
                $this->amzAwsId,
                $this->amzAwsSecret,
                $this->amzArn
            )
        );
        $this->sdk = SellingPartnerSDK::create($client, $factory, $factory, $configuration, $this->logger);
    }


    public function getShipmentReceived()
    {
        $dateTime= new DateTime();
        $dateTime->sub(new DateInterval('P30D'));

        return $this->sdk->fulfillmentOutbound()->listAllFulfillmentOrders(
            $this->getAccessToken(),
            Regions::EUROPE,
            $dateTime
        );
    }


    public function getParcelShipments($orderId)
    {
        return $this->sdk->fulfillmentOutbound()->getFulfillmentOrder(
            $this->getAccessToken(),
            Regions::EUROPE,
            $orderId
        );
    }



    public function createInboundPlan(CreateInboundShipmentPlanRequest $request)
    {
        return  $this->sdk->fulfillmentInbound()->createInboundShipmentPlan($this->getAccessToken(), Regions::EUROPE, $request);
    }



    public function getShipmentSent()
    {
        return $this->sdk->fulfillmentInbound()->getShipments(
            $this->getAccessToken(),
            Regions::EUROPE,
            'SHIPMENT',
            Marketplace::GB()->id(),
            ['SHIPPED']
        );
    }


    public function getShipmentItems($shipmentId)
    {
        return $this->sdk->fulfillmentInbound()->getShipmentItemsByShipmentId(
            $this->getAccessToken(),
            Regions::EUROPE,
            $shipmentId,
            Marketplace::GB()->id(),
        );
    }



    public function getLabels($shipmentId)
    {
        return $this->sdk->fulfillmentInbound()->getLabels(
            $this->getAccessToken(),
            Regions::EUROPE,
            $shipmentId,
            'PackageLabel_Thermal',
            'PALLET',
        );
    }


    public function createInbound($shipmentId, InboundShipmentRequest $inboundShipmentRequest)
    {
        return $this->sdk->fulfillmentInbound()->createInboundShipment(
            $this->getAccessToken(),
            Regions::EUROPE,
            $shipmentId,
            $inboundShipmentRequest
        );
    }


    




    public function getReport($idReport)
    {
        return $this->sdk->reports()->getReport(
            $this->getAccessToken(),
            Regions::EUROPE,
            $idReport
        );
    }


    public function getAllFinancials($dateTime, $dateTimeFin)
    {
        $allEvents = [];
        $nextToken = null;
        do {
            $reponse = $this->sdk->finances()->listFinancialEventGroups(
                $this->getAccessToken(),
                Regions::EUROPE,
                100,
                $dateTimeFin,
                $dateTime,
                $nextToken
            );
            $payLoad = $reponse->getPayload();
            $allEvents = array_merge($allEvents, $payLoad->getFinancialEventGroupList());
            $nextToken = $payLoad->getNextToken();
        } while ($nextToken);

        return $allEvents;
    }

    public function getFinancialEventsInGroup($groupEventId)
    {
        return $this->getFinancialEventPer('listFinancialEventsByGroupId', $groupEventId);
    }


    public function getFinancialEventsInOrder($amzonOrderId)
    {
        return $this->getFinancialEventPer('listFinancialEventsByOrderId', $amzonOrderId);
    }


    public function getFinancialEventPer($type, $typeId)
    {
        $allEvents = [];
        $nextToken = null;
        $counter = 1;
        do {
            $this->logger->info('Batch ' . $counter);
            $reponse = $this->sdk->finances()->{$type}(
                $this->getAccessToken(),
                Regions::EUROPE,
                $typeId,
                100,
                $nextToken
            );
            $payLoad = $reponse->getPayload();
            $allEvents[] = $payLoad->getFinancialEvents();
            $nextToken = $payLoad->getNextToken();
            $counter++;
        } while ($nextToken);

        return $allEvents;
    }





    public function getAllReports(array $type, array $status = [], DateTime $createdSince = null, $marketplaces = null)
    {
        $reports = [];
        $status = count($status) > 0 ? $status : $this->getAllStatusReport();
        $nextToken = null;
        do {
            $reponse = $this->sdk->reports()->getReports(
                $this->getAccessToken(),
                Regions::EUROPE,
                $type,
                $status,
                $marketplaces,
                10,
                $createdSince,
                null,
                $nextToken
            );

            $reports = array_merge($reports, $reponse->getReports());
            $nextToken = $reponse->getNextToken();
        } while ($nextToken);
        return $reports;
    }


    public function getContentLastReport(string $type, DateTime $createdSince = null, $marketplaces = null)
    {
        $report = $this->getLastReport($type, [self::STATUS_REPORT_DONE], $createdSince, $marketplaces);
        return $report ? $this->getContentReport($report->getReportDocumentId()) : null;
    }


    public function getContentReport($documentReportId, $toArray = true)
    {
        $response = $this->sdk->reports()->getReportDocument(
            $this->getAccessToken(),
            Regions::EUROPE,
            $documentReportId
        );
        $decrypted_data = file_get_contents($response->getUrl());
        //$encryptedMethod = $response->getCompressionAlgorithmAllowableValues();
        //$decrypted_data = openssl_decrypt($textEncrypted, "aes-256-cbc", base64_decode($encryptedMethod->getKey()), OPENSSL_RAW_DATA, base64_decode($encryptedMethod->getInitializationVector()));
        
        return $toArray ? $this->transformDocumentReportToArray($decrypted_data) : $decrypted_data;
    }


    public function getLastReport(string $type, array $status = [self::STATUS_REPORT_DONE], DateTime $createdSince = null, $marketplaces = null)
    {
        $reports = $this->getAllReports([$type], $status, $createdSince, $marketplaces);

        if ($marketplaces) {
            $reportsMarketplace = [];
            foreach ($reports as $report) {
                $markeplaceids = $report->getMarketplaceIds();
                if (count($markeplaceids)==1) {
                    $reportsMarketplace []= $report;
                }
            }
            return end($reportsMarketplace);
        } else {
            return end($reports);
        }
    }


    public function createReport(DateTime $dateTimeStart, $reportType, $marketplaces = null)
    {
        $this->logger->info("Report creation $reportType from " . $dateTimeStart->format("Y-m-d"));
        $configurationReport = new CreateReportSpecification();
        $configurationReport->setReportType($reportType);
        $configurationReport->setDataStartTime($dateTimeStart);
        if ($marketplaces) {
            $configurationReport->setMarketplaceIds($marketplaces);
        } else {
            $configurationReport->setMarketplaceIds($this->getAllMarketplaces());
        }
        
        $reponse = $this->sdk->reports()->createReport(
            $this->getAccessToken(),
            Regions::EUROPE,
            $configurationReport,
        );
        return $reponse;
    }


    public function createReportStartEnd(DateTime $dateTimeStart, DateTime $dateTimeEnd, $reportType, $marketplaces = null)
    {
        $this->logger->info("Report creation $reportType from " . $dateTimeStart->format("Y-m-d"));
        $configurationReport = new CreateReportSpecification();
        $configurationReport->setReportType($reportType);
        $configurationReport->setDataStartTime($dateTimeStart);
        $configurationReport->setDataEndTime($dateTimeEnd);
        if ($marketplaces) {
            $configurationReport->setMarketplaceIds($marketplaces);
        } else {
            $configurationReport->setMarketplaceIds($this->getAllMarketplaces());
        }
        
        $reponse = $this->sdk->reports()->createReport(
            $this->getAccessToken(),
            Regions::EUROPE,
            $configurationReport,
        );
        return $reponse;
    }




    private function transformDocumentReportToArray($decryptedData)
    {
        $datas = [];
        $contentArray =  explode("\r\n", (string) $decryptedData);
        $header = explode("\t", array_shift($contentArray));
        foreach ($contentArray as $contentLine) {
            $values = explode("\t", $contentLine);
            if (count($values) == count($header)) {
                $datas[] = array_combine($header, $values);
            }
        }
        return $datas;
    }


    private function getAllStatusReport()
    {
        return [
            self::STATUS_REPORT_FATAL,
            self::STATUS_REPORT_DONE,
            self::STATUS_REPORT_CANCELLED,
            self::STATUS_REPORT_FATAL,
            self::STATUS_REPORT_IN_PROGRESS,
            self::STATUS_REPORT_IN_QUEUE
        ];
    }


    public function getProductData($asin)
    {
        $marketplaces = $this->getAllMarketplaces();

        foreach ($marketplaces as $marketplace) {
            $response = $this->sdk->catalogItem()->getCatalogItem(
                $this->getAccessToken(),
                Regions::EUROPE,
                $asin,
                [$marketplace]
            );
        
            $reponseSummary = $response->getSummaries();
            foreach ($reponseSummary as $summary) {
                return $summary;
            }

        }
        return null;
    }



    public function getOrdersUpdatedAfter(DateTime $dateTime)
    {
        $orders = [];
        $nextToken = null;
        while (true) {
            $response = $this->sdk->orders()->getOrders(
                $this->getAccessToken(),
                Regions::EUROPE,
                $this->getAllMarketplaces(),
                null,
                null,
                $this->formateDate($dateTime),
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                $nextToken
            );

            $orderList = $response->getPayload();
            $orderReturn = $orderList->getOrders();
            $orders = array_merge($orders, $orderReturn);
            if ($orderList->getNextToken()) {
                $nextToken = $orderList->getNextToken();
            } else {
                return  $orders;
            }
        }
    }

    public function getOrderItems($orderNumber)
    {
        $response = $this->sdk->orders()->getOrderItems(
            $this->getAccessToken(),
            Regions::EUROPE,
            $orderNumber
        );

        return $response->getPayload()->getOrderItems();
    }

    private function getAccessToken()
    {
        if ($this->checkIfWeNeedNewToken()) {
            $this->accessToken = $this->sdk->oAuth()->exchangeRefreshToken($this->amzRefreshToken);
            $this->dateInitialisationToken = new DateTime();
        }
        return $this->accessToken;
    }


    private function checkIfWeNeedNewToken()
    {
        if (!$this->accessToken || !$this->dateInitialisationToken) {
            return true;
        }
        $dateNow = new DateTime();
        $diffMin = abs($dateNow->getTimestamp() - $this->dateInitialisationToken->getTimestamp());
        return $this->accessToken->expiresIn() < $diffMin;
    }

    private function formateDate(DateTime $date)
    {
        return substr($date->format('c'), 0, 19) . "Z";
    }


    public function getAllMarketplaces()
    {
        return [
            Marketplace::fromCountry('ES')->id(),
            Marketplace::fromCountry('FR')->id(),
            Marketplace::fromCountry('DE')->id(),
            Marketplace::fromCountry('IT')->id(),
            Marketplace::fromCountry('GB')->id(),
        ];
    }



    public function getListingForSku($sku, $marketplace){
            $response = $this->sdk->listingsItems()->getListingsItem(
                $this->getAccessToken(),
                Regions::EUROPE, 
                $this->amzSellerId, 
                $sku, 
                [$marketplace],
                null,
                ['offers', 'summaries', 'issues','fulfillmentAvailability']
            );
            return $response;
    }



}
