<?php

namespace App\Service\ChannelAdvisor;

use App\Entity\WebOrder;
use App\Helper\Api\ApiInterface;
use DateTime;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use stdClass;

class ChannelAdvisorApi implements ApiInterface
{
    public function getChannel()
    {
        return WebOrder::CHANNEL_CHANNELADVISOR;
    }

    public const AUTH_URL = 'https://api.channeladvisor.com/oauth2/token';

    public const API_URL = 'https://api.channeladvisor.com/v1/';

    protected $refreshToken;

    protected $applicationId;

    protected $sharedSecret;

    protected $accessToken;

    public const TIME_TO_REFRESH_TOKEN = 30;

    protected $dateInitialisationToken;

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
            'form_params' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->refreshToken,
            ],
        ]);
        $body = json_decode($response->getBody());
        $this->accessToken = $body->access_token;
        $this->dateInitialisationToken = new DateTime();
    }


    public function refreshAccessToken()
    {
        if ($this->checkIfTokenTooOld()) {
            $this->getAccessToken();
        }
    }


    private function checkIfTokenTooOld(): bool
    {
        $dateNow = new DateTime();
        $diffMin = abs($dateNow->getTimestamp() - $this->dateInitialisationToken->getTimestamp()) / 60;

        return $diffMin > self::TIME_TO_REFRESH_TOKEN;
    }


    public function getOrders($params = [])
    {
        return $this->sendRequest('Orders', $params);
    }

    public function getOrder(string $orderId)
    {
        return $this->sendRequest('Orders(' . $orderId . ')');
    }

    /**
     * @param string $link
     *
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
            '$expand' => 'Items($expand=Adjustments,Promotions,BundleComponents),Fulfillments($expand=Items),Adjustments',
            '$filter' => "PaymentStatus eq 'Cleared' and CheckoutStatus eq 'Completed' and CreatedDateUtc gt 2022-04-01",
        ];
        if ($notExported) {
            $params['exported'] = 'false';
        }

        return $this->getOrders($params);
    }

    public function markOrderAsExported($orderId)
    {
        return $this->sendRequest('Orders(' . $orderId . ')/Export', [], 'POST');
    }

    public function markOrderAsNonExported($orderId)
    {
        return $this->sendRequest('Orders(' . $orderId . ')/Export', [], 'DELETE');
    }

    public function notifyShipping($orderId, $toSend)
    {
        return $this->sendRequest('Orders(' . $orderId . ')/Ship', [], 'POST', $toSend);
    }

    public function sendInvoice(
        $profileId,
        $orderId,
        $totalAmount,
        $totalVATAAmount,
        $invoiceNumber,
        $dataFile
    ) {
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

    public function sendCredit(
        $profileId,
        $orderId,
        $adjustmentID,
        $totalAmount,
        $totalVATAAmount,
        $invoiceNumber,
        $dataFile
    ) {
        $params = [
            'ProfileID' => $profileId,
            'OrderID' => $orderId,
            'DocumentType' => 'AmazonVATCreditNote',
            'TotalAmount' => str_replace(',', '.', $totalAmount),
            'TotalVATAmount' => str_replace(',', '.', $totalVATAAmount),
            'InvoiceNumber' => $invoiceNumber,
            'AdjustmentID' => $adjustmentID,
        ];

        return $this->sendDocuments($params, $dataFile);
    }

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

        return 204 == $response->getStatusCode();
    }

    public function sendRefund($orderLineId, $toSend)
    {
        return $this->sendRequest('OrderItems(' . $orderLineId . ')/Adjust', [], 'POST', $toSend);
    }

    public function sendRequest($endPoint, $queryParams = [], $method = 'GET', $form = null)
    {
        $this->refreshAccessToken();
        $query = array_merge(['access_token' => $this->accessToken], $queryParams);
        $parameters = ['query' => $query];
        if ('GET' != $method && $form) {
            $parameters['json'] = $form;
        }

        $client = new Client();
        $response = $client->request($method, self::API_URL . $endPoint, $parameters);

        return json_decode($response->getBody());
    }

    public function getOrderByNumber($number, $profileId)
    {
        $params = [
            '$filter' => "SiteOrderID eq '$number' and ProfileID eq $profileId",
        ];

        $orderResults = $this->getOrders($params);
        if (count($orderResults->value) > 0) {
            $firstOrder = array_shift($orderResults->value);

            return $firstOrder->ID;
        }

        return null;
    }

    public function getFullOrder($channelId)
    {
        $expand = 'Items($expand=Adjustments,Promotions,BundleComponents),Fulfillments($expand=Items),Adjustments';

        return $this->sendRequest(
            'Orders(' . $channelId . ')',
            [
                '$expand' => $expand,
            ]
        );
    }

    public function getAllDocumentsOrder($orderId)
    {
        return $this->sendRequest('Orders(' . $orderId . ')/ChannelDocuments');
    }

    public function getAllOrdersToSend()
    {
        return $this->getAllNewOrders(true);
    }

    public function getAllNewOrders($notExported = true): array
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
                ++$i;
                $this->logger->info("Get batch $i >> " . count($orderRetrieve) . ' orders');
            } else {
                break;
            }
        }

        return $orderRetrieve;
    }
}
