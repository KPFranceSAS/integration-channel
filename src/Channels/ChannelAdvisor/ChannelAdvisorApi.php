<?php

namespace App\Channels\ChannelAdvisor;

use App\Entity\IntegrationChannel;
use App\Service\Aggregator\ApiInterface;
use DateTime;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use stdClass;

class ChannelAdvisorApi implements ApiInterface
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_CHANNELADVISOR;
    }

    public const AUTH_URL = 'https://api.channeladvisor.com/oauth2/token';

    public const API_URL = 'https://api.channeladvisor.com/v1/';

    protected $channelEndpoint;

    protected $refreshToken;

    protected $applicationId;

    protected $sharedSecret;

    protected $accessToken;

    public const TIME_TO_REFRESH_TOKEN = 30;

    protected $dateInitialisationToken;

    protected $logger;

    public function __construct(LoggerInterface $logger, $channelEndpoint, $channelRefreshToken, $channelApplicationId, $channelSharedSecret)
    {
        $this->logger = $logger;
        $this->channelEndpoint = 'https://'.$channelEndpoint;
        $this->refreshToken = $channelRefreshToken;
        $this->applicationId = $channelApplicationId;
        $this->sharedSecret = $channelSharedSecret;
        
    }


    private function getApiEndPoint()
    {
        return $this->channelEndpoint."/v1/";
    }

    private function getAuthEndPoint()
    {
        return $this->channelEndpoint."/oauth2/token";
    }

    private function getAccessToken()
    {
        $this->logger->info('Iniatialise token Channeladvisor');
        $client = new Client();
        $response = $client->request('POST', $this->getAuthEndPoint(), [
            'auth' => [$this->applicationId, $this->sharedSecret],
            'form_params' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->refreshToken,
            ],
            'debug' => false
        ]);
        $body = json_decode($response->getBody());
        $this->accessToken = $body->access_token;
        $this->logger->info('Iniatialised token Channeladvisor '.$this->accessToken);
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
        if(!$this->dateInitialisationToken) {
            return true;
        }

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
        $this->logger->info('Get orders Channeladvisor');
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

    public function markOrderAsFulfill($orderId, $trackingNumber,$trackingUrl, $carrierService)
    {
        $params = [
            'TrackingNumber' => $trackingNumber,
            'ShippingCarrier' => $carrierService,
            'ShippingClass' => 'Standard',
            'TrackingUrl' =>  $trackingUrl,
        ];


        return $this->sendRequest('Orders(' . $orderId . ')/Ship', [], 'POST', $params);
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
        $response = $client->request('POST', $this->getApiEndPoint(). 'ChannelDocuments', $parameters);

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
        $parameters = [
            'query' => $query,
            'debug' => false
        ];
        if ('GET' != $method && $form) {
            $parameters['json'] = $form;
        }

        $client = new Client();
        $response = $client->request($method, $this->getApiEndPoint() . $endPoint, $parameters);

        return json_decode($response->getBody());
    }

    public function getOrderByNumber($number)
    {
        return $this->getFirstOrder([
            '$filter' => "SiteOrderID eq '$number'",
        ]);
    }


    public function getOrderByNumberProfile($number, $profileId)
    {
        return $this->getFirstOrder([
            '$filter' => "SiteOrderID eq '$number' and ProfileID eq $profileId",
        ]);
    }


    public function getFirstOrder($params)
    {
        $orderResults = $this->getOrders($params);
        if (count($orderResults->value) > 0) {
            $firstOrder = array_shift($orderResults->value);

            return $this->getFullOrder($firstOrder->ID);
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



    public function getAllOrdersBy($params, $notExported=false): array
    {
        $i = 1;
        $orderRetrieve = [];

        $paramsRequest = [
            '$expand' => 'Items($expand=Adjustments,Promotions,BundleComponents),Fulfillments($expand=Items),Adjustments',
            '$filter' => $params,
        ];

        if ($notExported) {
            $paramsRequest['exported'] = 'false';
        }


        $ordersApi = $this->getOrders($paramsRequest);

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
