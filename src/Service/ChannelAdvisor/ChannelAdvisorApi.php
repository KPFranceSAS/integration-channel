<?php

namespace App\Service\ChannelAdvisor;

use App\Entity\WebOrder;
use App\Helper\Api\ApiInterface;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use stdClass;

class ChannelAdvisorApi implements ApiInterface
{

    public function getChannel()
    {
        return WebOrder::CHANNEL_CHANNELADVISOR;
    }


    const AUTH_URL = 'https://api.channeladvisor.com/oauth2/token';

    const API_URL = 'https://api.channeladvisor.com/v1/';

    /**
     *
     * @var string 
     */
    protected $refreshToken;

    /**
     *
     * @var string 
     */
    protected $applicationId;

    /**
     *
     * @var string 
     */
    protected $sharedSecret;

    /**
     *
     * @var string 
     */
    protected $accessToken;


    /**
     * Delay to refresh token for channeladvisor
     */
    const TIME_TO_REFRESH_TOKEN = 30;


    /**
     *
     * @var \DateTime 
     */
    protected $dateInitialisationToken;

    /**
     *
     * @var LoggerInterface
     */
    protected $logger;


    public function __construct(LoggerInterface $logger, $refreshToken, $applicationId, $sharedSecret)
    {
        $this->logger = $logger;
        $this->refreshToken = $refreshToken;
        $this->applicationId = $applicationId;
        $this->sharedSecret = $sharedSecret;
        $this->getAccessToken();
    }



    private function getAccessToken()
    {
        $client = new Client();
        $response = $client->request('POST', self::AUTH_URL, [
            'auth' => [$this->applicationId, $this->sharedSecret],
            'form_params' => ['grant_type' => 'refresh_token', 'refresh_token' => $this->refreshToken]
        ]);
        $body = json_decode($response->getBody());
        $this->accessToken = $body->access_token;
        $this->dateInitialisationToken = new \DateTime();
    }

    /**
     * Check if token nedd to be regenerate
     *
     * @return void
     */
    public function refreshAccessToken()
    {
        if ($this->checkIfTokenTooOld()) {
            $this->getAccessToken();
        }
    }

    /**
     * Check if Token creation is older than TIME_TO_REFRESH_TOKEN
     *
     * @return void
     */
    private function checkIfTokenTooOld()
    {
        $dateNow = new \DateTime();
        $diffMin = abs($dateNow->getTimestamp() - $this->dateInitialisationToken->getTimestamp()) / 60;
        return $diffMin > self::TIME_TO_REFRESH_TOKEN;
    }



    /**
     * 
     * @param array $params
     * @return stdClass
     */
    public function getOrders($params = [])
    {
        return $this->sendRequest('Orders', $params);
    }



    /**
     * 
     * @param array $params
     * @return \stdClass
     */
    public function getOrder($orderId, $params = []): stdClass
    {
        return $this->sendRequest('Orders(' . $orderId . ')', $params);
    }



    /**
     * 
     * @param string $link
     * @return stdClass
     */
    public function getNextResults($link)
    {
        $client = new Client();
        $response = $client->request('GET', $link);
        return json_decode($response->getBody());
    }


    public function getNewOrdersByBatch($notExported = true)
    {
        $params = [
            '$expand' => 'Items($expand=Adjustments,Promotions, BundleComponents),Fulfillments($expand=Items),Adjustments',
            '$filter' => "PaymentStatus eq 'Cleared' and CheckoutStatus eq 'Completed' and CreatedDateUtc gt 2022-04-01"
        ];
        if ($notExported) {
            $params['exported'] = 'false';
        }
        return $this->getOrders($params);
    }

    /**
     * Send request to mark an order as exported
     * @param int $orderId
     * @return stdClass
     */
    public function markOrderAsExported($orderId)
    {
        return $this->sendRequest('Orders(' . $orderId . ')/Export', [], 'POST');
    }

    /**
     * Send request to mark an order as non exported
     * @param int $orderId
     * @return stdClass
     */
    public function markOrderAsNonExported($orderId)
    {
        return $this->sendRequest('Orders(' . $orderId . ')/Export', [], 'DELETE');
    }


    /**
     * Send a request to notify that a product was shipped
     * @param integer $orderId
     * @param stdClass $toSend
     * @return stdClass
     */
    public function notifyShipping($orderId, $toSend)
    {
        return $this->sendRequest('Orders(' . $orderId . ')/Ship', [], 'POST', $toSend);
    }



    /**
     * Undocumented function
     *
     * @param int $profileId
     * @param int $orderId
     * @param string $totalAmount
     * @param string $totalVATAAmount
     * @param string $invoiceNumber
     * @param string $dataFile
     * @return void
     */
    public function sendInvoice($profileId, $orderId, $totalAmount, $totalVATAAmount, $invoiceNumber, $dataFile)
    {
        $params = [
            'ProfileID' => $profileId,
            'OrderID' => $orderId,
            'DocumentType' => 'AmazonVATInvoice',
            'TotalAmount' => str_replace(',', '.', $totalAmount),
            'TotalVATAmount' => str_replace(',', '.', $totalVATAAmount),
            'InvoiceNumber' => $invoiceNumber,
        ];
        return $this->sendDocuments($params, $dataFile);
    }

    /**
     * Undocumented function
     *
     * @param int $profileId
     * @param int $orderId
     * @param int$adjustmentID
     * @param string $totalAmount
     * @param string $totalVATAAmount
     * @param string $invoiceNumber
     * @param string $dataFile
     * @return void
     */
    public function sendCredit($profileId, $orderId, $adjustmentID, $totalAmount, $totalVATAAmount, $invoiceNumber, $dataFile)
    {
        $params = [
            'ProfileID' => $profileId,
            'OrderID' => $orderId,
            'DocumentType' => 'AmazonVATCreditNote',
            'TotalAmount' => str_replace(',', '.', $totalAmount),
            'TotalVATAmount' => str_replace(',', '.', $totalVATAAmount),
            'InvoiceNumber' => $invoiceNumber,
            'AdjustmentID' => $adjustmentID
        ];
        return $this->sendDocuments($params, $dataFile);
    }


    /**
     * Undocumented function
     *
     * @param [type] $queryParams
     * @param [type] $dataFile
     * @return void
     */
    private function sendDocuments($queryParams, $dataFile)
    {
        $this->refreshAccessToken();
        $query = array_merge(['access_token' => $this->accessToken], $queryParams);
        $parameters = [
            'query' => $query,
            'body' => $dataFile,
            'headers' => ['Content-Type' => 'application/pdf'],
        ];
        $client = new Client();
        $response = $client->request('POST', self::API_URL . 'ChannelDocuments', $parameters);
        return $response->getStatusCode() == 204;
    }




    /**
     * Send a request to force channeladvisor to porcces a refund 
     * @param integer $orderLineId
     * @param stdClass $toSend
     * @return stdClass
     */
    public function sendRefund($orderLineId, $toSend)
    {
        return $this->sendRequest('OrderItems(' . $orderLineId . ')/Adjust', [], 'POST', $toSend);
    }



    public function sendRequest($endPoint, $queryParams = [], $method = 'GET', $form = null)
    {
        $this->refreshAccessToken();
        $query = array_merge(['access_token' => $this->accessToken], $queryParams);
        $parameters = array('query' => $query);
        if ($method != 'GET' && $form) {
            $parameters['json'] = $form;
        }

        $client = new Client();
        $response = $client->request($method, self::API_URL . $endPoint, $parameters);
        return json_decode($response->getBody());
    }


    /**
     * fetch new order and asve in the database in order to be integrated in sage.
     */
    public function getOrderByNumber($number, $profileId)
    {
        $params = [
            '$filter' => "SiteOrderID eq '$number' and ProfileID eq $profileId"
        ];

        $orderResults = $this->getOrders($params);
        if (count($orderResults->value) > 0) {
            $firstOrder = array_shift($orderResults->value);
            return $firstOrder->ID;
        }
        return null;
    }



    /**
     * get info from orders to fit channeladvisor one.
     */
    public function getFullOrder($channelId)
    {
        $params = [
            '$expand' => 'Items($expand=Adjustments,Promotions, BundleComponents),Fulfillments($expand=Items),Adjustments'
        ];
        return $this->getOrder($channelId, $params);
    }


    /**
     * get info from orders to fit channeladvisor one.
     */
    public function getAllDocumentsOrder($orderId)
    {

        return $this->sendRequest('Orders(' . $orderId . ')/ChannelDocuments');
    }




    public function getAllOrdersToSend()
    {
        return $this->getAllNewOrders(true);
    }



    /**
     * fetch new orders and return them in an array
     * @return array
     */
    public function getAllNewOrders($notExported = true)
    {
        $i = 1;
        $orderRetrieve = [];
        $ordersApi = $this->getNewOrdersByBatch($notExported);

        foreach ($ordersApi->value as $orderApi) {
            $orderRetrieve[] = $orderApi;
        }
        $this->logger->info("Get batch $i >> " . count($orderRetrieve) . ' orders');

        while (true) {
            if (property_exists($ordersApi, '@odata.nextLink')) {
                $ordersApi = $this->getNextResults($ordersApi->{'@odata.nextLink'});
                foreach ($ordersApi->value as $orderApi) {
                    $orderRetrieve[] = $orderApi;
                }
                $i++;
                $this->logger->info("Get batch $i >> " . count($orderRetrieve) . ' orders');
            } else {
                break;
            }
        }
        return $orderRetrieve;
    }
}
