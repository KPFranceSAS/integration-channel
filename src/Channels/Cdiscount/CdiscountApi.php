<?php

namespace App\Channels\Cdiscount;

use App\Entity\IntegrationChannel;
use App\Service\Aggregator\ApiInterface;
use DateTime;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\Log\LoggerInterface;
use stdClass;

class CdiscountApi implements ApiInterface
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_CDISCOUNT;
    }

    public const AUTH_URL = 'https://auth.octopia.com/auth/realms/maas/protocol/openid-connect/token';

    public const API_URL = 'https://api.octopia-io.net/seller/v2/';


    protected $cdiscountClientId;
    protected $cdiscountSellerId;
    protected $cdiscountClientSecret;

    protected $accessToken;

    public const TIME_TO_REFRESH_TOKEN = 30;

    protected $dateInitialisationToken;

    protected $logger;

    public function __construct(LoggerInterface $logger, $cdiscountClientId, $cdiscountClientSecret, $cdiscountSellerId)
    {
        $this->logger = $logger;
        $this->cdiscountClientId = $cdiscountClientId;
        $this->cdiscountSellerId = $cdiscountSellerId;
        $this->cdiscountClientSecret = $cdiscountClientSecret;
    }



    private function getAccessToken()
    {
        $this->logger->info('Get access token');
        $client = new Client();
        $response = $client->request('POST', self::AUTH_URL, [
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => $this->cdiscountClientId,
                'client_secret' => $this->cdiscountClientSecret,
            ],
            'debug' => true
        ]);
        $body = json_decode($response->getBody());
        $this->logger->info('Get access token response'.json_encode($body));
        $this->accessToken = $body->access_token;
        $this->dateInitialisationToken = new DateTime();
    }


    public function refreshAccessToken()
    {
        if(!$this->accessToken || $this->checkIfTokenTooOld()) {
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


    /**
     * @param array $params
     * @return array
     */
    public function getOrders(array $params = [])
    {
        $offset = 1;
        $max_page = 2;
        $orders = [];
        while ($offset  < $max_page) {
            $params ['page'] = $offset;
            $this->logger->info('Get orders batch n°' . $offset . ' / ' . $max_page . ' >>' . json_encode($params));
            $reponse =  $this->sendRequest('orders/v1/orders', $params);
            $orders = array_merge($orders, $reponse['content']);
            $max_page  = $reponse['pagination']['pages'];
        }
        
        return $orders;
    }

    public function getAllOrdersToSend()
    {
        $pendings = $this->getOrders(['status' => 'PENDING']);
        $preparations = $this->getOrders(['status' => 'PREPARATION']);
        $orders = array_merge($pendings, $preparations);
        return $orders;
    }

   
    public function getOrder(string $orderNumber)
    {
       
    }

    public const PAGINATION = 50;


    public function markOrderAsFulfill($orderId, $carrierCode, $carrierName, $carrierUrl, $trackingNumber):bool
    {
        $bodyRequest = [
            [
              "parcelNumber" =>$trackingNumber,
              "carrierName" => $carrierCode,
              "trackingUrl" => $carrierUrl,
            ]
        ];
        $this->sendRequest('orders/'.$orderId.'/shipments', [], 'POST', $bodyRequest);
        return true;
    }


    public function searchProductByGtin($gtin)
    {
        $products = $this->sendRequest('products', ['gtin'=>$gtin]);
        if(count($products['items'])>0){
            return $products['items'][0];
        } else {
            return null;
        }    
    }


    public function sendProducts(array $products)
    {
       
        return $this->sendRequest('products-integration', [], 'POST', json_encode(['products' => $products]));
    }





    public function sendRequest($endPoint, $queryParams = [], $method = 'GET', $body = null)
    {

        $this->refreshAccessToken();

        $this->logger->info('Send request '.$endPoint);
        $client = new Client(
            ['debug'=>true]
        );
        $headers = [
            "Authorization"=> "Bearer " .$this->accessToken,
            'SellerId' => $this->cdiscountSellerId,
            'Content-Type' => "application/json",
            'Accept-Language' => "fr-FR",
            'Accept' => "application/json"
        ];
        $url = self::API_URL. $endPoint;
        if (count($queryParams)>0) {
            $urlSegments=[];
            foreach ($queryParams as $keyParam => $param) {
                $urlSegments[]=$keyParam.'='.$param;
            }
            $url.='?'.implode('&', $urlSegments);
        }
        $request = new Request($method, $url, $headers, $body);
        $response = $client->sendRequest($request);
        return json_decode($response->getBody(), true);
    }

}
