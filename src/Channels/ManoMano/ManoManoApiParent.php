<?php

namespace App\Channels\ManoMano;

use App\Service\Aggregator\ApiInterface;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\Log\LoggerInterface;

abstract class ManoManoApiParent implements ApiInterface
{
    protected $client;


    protected $logger;


    protected $clientUrl;


    protected $clientKey;

    protected $contractId;


    public function __construct(LoggerInterface $logger, string $clientUrl, string $clientKey, string $contractId)
    {
        $this->logger = $logger;
        $this->clientUrl = $clientUrl;
        $this->clientKey = $clientKey;
        $this->contractId = $contractId;
    }

    
    /**
     * Summary of GetOrdersRequest
     * order_ids
     * order_references_for_customer
     * start_date end_date
     * order_state_codes
     * STAGING, WAITING_ACCEPTANCE, WAITING_DEBIT, WAITING_DEBIT_PAYMENT, SHIPPING, SHIPPED, TO_COLLECT, RECEIVED, CLOSED, REFUSED, CANCELED
     * @param array $params
     * @return array
     */
    public function getOrders(array $params = [])
    {
        $offset = 1;
        $max_page = 1;
        $orders = [];
        while ($offset  <= $max_page) {
            $params ['seller_contract_id'] = $this->contractId;
            $realOffset =  $offset+1;
            $this->logger->info('Get orders batch n°' . $realOffset . ' / ' . $max_page . ' >>' . json_encode($params));
            $reponse =  $this->sendRequest('orders/v1/orders', $params);
            $orders = array_merge($orders, $reponse['content']);
            $max_page  = $reponse['pagination']['pages'];
        }
        
        return $orders;
    }


    public function getAllOrdersToSend()
    {
        $params = [
            'status' => 'PENDING'
        ];
        return $this->getOrders($params);
    }


    

   
    public function getOrder(string $orderNumber)
    {
        $this->logger->info('Get Order  ' . $orderNumber);
        $reponse =  $this->sendRequest('orders/v1/orders/'.$orderNumber, ['seller_contract_id'=>$this->contractId]);
        return $reponse;
    }


    


    public const PAGINATION = 50;




    public function markOrderAsFulfill($orderId, $carrierCode, $carrierName, $carrierUrl, $trackingNumber):bool
    {
        return true;
    }

    public function markOrderAsAccepted($order): bool
    {
        $body = [
            [
              "order_reference" => $order['order_reference'],
              "seller_contract_id" =>$this->contractId,
            ]
          ];
        $reponse =  $this->sendRequest('orders/v1/accept-orders', [], 'POST', json_encode($body));

        return true;
    }
        


    public function sendRequest($endPoint, $queryParams = [], $method = 'GET', $body = null)
    {
        $client = new Client();
        $headers = [
            "x-api-key"=>$this->clientKey,
            'x-thirdparty-name' => 'KPS_Patxira'
        ];
        $url = $this->clientUrl."/". $endPoint;
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
