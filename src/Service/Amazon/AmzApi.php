<?php

namespace App\Service\Amazon;

use AmazonPHP\SellingPartner\Configuration;
use AmazonPHP\SellingPartner\Marketplace;
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

    const TYPE_REPORT_LAST_UPDATE_ORDERS = 'GET_FLAT_FILE_ALL_ORDERS_DATA_BY_LAST_UPDATE_GENERAL';
    const TYPE_REPORT_LAST_UPDATE_ARCHIVED_ORDERS = 'GET_FLAT_FILE_ARCHIVED_ORDERS_DATA_BY_ORDER_DATE';
    const TYPE_REPORT_LISTINGS_ALL_DATA = 'GET_MERCHANT_LISTINGS_ALL_DATA';
    const TYPE_REPORT_OPEN_LISTINGS_DATA = 'GET_FLAT_FILE_OPEN_LISTINGS_DATA';
    const TYPE_REPORT_RETURNS_DATA = 'GET_FLAT_FILE_RETURNS_DATA_BY_RETURN_DATE';
    const TYPE_REPORT_INVENTORY_DATA_BY_COUNTRY = 'GET_AFN_INVENTORY_DATA_BY_COUNTRY';
    const TYPE_REPORT_INVENTORY_DATA = 'GET_AFN_INVENTORY_DATA';

    const STATUS_REPORT_DONE = 'DONE';
    const STATUS_REPORT_CANCELLED = 'CANCELLED';
    const STATUS_REPORT_FATAL = 'FATAL';
    const STATUS_REPORT_IN_PROGRESS = 'IN_PROGRESS';
    const STATUS_REPORT_IN_QUEUE = 'IN_QUEUE';




    private $amzLwaId;

    private $amzLwaSecret;

    private $amzAwsId;

    private $amzAwsSecret;

    private $amzArn;

    private $amzRefreshToken;

    private $sdk;

    private $logger;

    private $accessToken;

    public function __construct(LoggerInterface $logger, string $amzLwaId, string $amzLwaSecret, string $amzAwsId, string $amzAwsSecret, string $amzArn, string $amzRefreshToken)
    {

        $this->amzLwaId = $amzLwaId;
        $this->amzLwaSecret = $amzLwaSecret;
        $this->amzAwsId = $amzAwsId;
        $this->amzAwsSecret = $amzAwsSecret;
        $this->amzRefreshToken = $amzRefreshToken;
        $this->amzArn = $amzArn;
        $this->logger = $logger;

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

    /***
     * Report creation and read
     */

    public function getContent(DateTime $dateTimeStart = null)
    {
        return $this->getContentLastReport(self::TYPE_REPORT_LAST_UPDATE_ORDERS, $dateTimeStart);
    }



    public function getContentLastReportOrdersByLastUpdate(DateTime $dateTimeStart = null)
    {
        return $this->getContentLastReport(self::TYPE_REPORT_LAST_UPDATE_ORDERS, $dateTimeStart);
    }


    public function getReport($idReport)
    {
        return $this->sdk->reports()->getReport(
            $this->getAccessToken(),
            Regions::EUROPE,
            $idReport
        );
    }




    public function createReportOrdersByLastUpdate(?DateTime $dateTimeStart = null)
    {
        if (!$dateTimeStart) {
            $dateTimeStart = new DateTime('now');
            $dateTimeStart->sub(new DateInterval('P3D'));
        }
        return $this->createReport($dateTimeStart, self::TYPE_REPORT_LAST_UPDATE_ORDERS);
    }


    public function getContentReport($documentReportId, $toArray = true)
    {
        $response = $this->sdk->reports()->getReportDocument(
            $this->getAccessToken(),
            Regions::EUROPE,
            $documentReportId
        );
        $textEncrypted = file_get_contents($response->getPayload()->getUrl());
        $encryptedMethod = $response->getPayload()->getEncryptionDetails();
        $decrypted_data = openssl_decrypt($textEncrypted, "aes-256-cbc", base64_decode($encryptedMethod->getKey()), OPENSSL_RAW_DATA,  base64_decode($encryptedMethod->getInitializationVector()));
        return $toArray ? $this->transformDocumentReportToArray($decrypted_data) : $decrypted_data;
    }


    public function getAllFinancials()
    {
        $dateTime = new DateTime('2019-12-01');
        $dateTimeFin = new DateTime('2020-01-01');
        $events = $this->sdk->finances()->listFinancialEventGroups(
            $this->getAccessToken(),
            Regions::EUROPE,
            100,

            $dateTimeFin,
            $dateTime,
        );
    }





    public function getAllReports(array $type, array $status = [], DateTime $createdSince = null)
    {
        $reports = [];
        $status = count($status) > 0 ? $status : $this->getAllStatusReport();
        $nextToken = null;
        while (true) {
            $reponse = $this->sdk->reports()->getReports(
                $this->getAccessToken(),
                Regions::EUROPE,
                $type,
                $status,
                null,
                10,
                $createdSince,
                null,
                $nextToken
            );
            $payLoad = $reponse->getPayload();
            $reports = array_merge($reports, $payLoad);
            if ($reponse->getNextToken()) {
                $nextToken = $reponse->getNextToken();
            } else {
                return $reports;
            }
        }
    }


    public function getContentLastReport(string $type, DateTime $createdSince = null)
    {
        $report = $this->getLastReport($type, [self::STATUS_REPORT_DONE], $createdSince);
        return $report ? $this->getContentReport($report->getReportDocumentId()) : null;
    }


    public function getLastReport(string $type, array $status = [self::STATUS_REPORT_DONE], DateTime $createdSince = null)
    {
        $reports = $this->getAllReports([$type], $status, $createdSince);
        return end($reports);
    }










    public function createReport(DateTime $dateTimeStart, $reportType)
    {
        $this->logger->info("Report creation $reportType from " . $dateTimeStart->format("Y-m-d"));
        $configurationReport = new CreateReportSpecification();
        $configurationReport->setReportType($reportType);
        $configurationReport->setDataStartTime($dateTimeStart);
        $configurationReport->setMarketplaceIds($this->getAllMarketplaces());
        $reponse = $this->sdk->reports()->createReport(
            $this->getAccessToken(),
            Regions::EUROPE,
            $configurationReport,
        );
        return $reponse->getPayload();
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
            if ($response->valid()) {
                $reponseSummary = $response->getSummaries();
                foreach ($reponseSummary as $summary) {
                    return $summary;
                }
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


    private function getAllMarketplaces()
    {
        return [
            Marketplace::ES()->id(),
            Marketplace::GB()->id(),
            Marketplace::FR()->id(),
            Marketplace::DE()->id(),
            Marketplace::IT()->id(),

        ];
    }


    private function transformDocumentReportToArray($decryptedData)
    {
        $datas = [];
        $contentArray =  explode("\r\n", $decryptedData);
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
}
