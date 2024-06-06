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
     * WAITING_PAYMENT, PENDING, REFUSED, PREPARATION, SHIPPED, REFUNDED, REFUNDING, REMORSE_PERIOD
     * @return array
     */
    public function getOrders(array $params = []): array
    {
        $offset = 1;
        $max_page = 1;
        $orders = [];
        while ($offset  <= $max_page) {
            $params ['seller_contract_id'] = $this->contractId;
            $params ['page'] = $offset;
            $this->logger->info('Get orders batch n°' . $offset . ' / ' . $max_page . ' >>' . json_encode($params));
            $reponse =  $this->sendRequest('orders/v1/orders', $params);

            $reponse= json_decode((string) $reponse->getBody(), true);
            $orders = array_merge($orders, $reponse['content']);
            $max_page  = $reponse['pagination']['pages'];
            $this->logger->info('Pagination '.json_encode($reponse['pagination']));
            $offset++;
        }
        
        return $orders;
    }


    

    public function getAllOrdersToAccept()
    {
        return $this->getOrders(['status' => 'PENDING']);
    }


    public function getAllOrdersToSend()
    {
        return $this->getOrders(['status' => 'PREPARATION']);
    }

   
    public function getOrder(string $orderNumber)
    {
        $this->logger->info('Get Order  ' . $orderNumber);
        $reponse =  $this->sendRequest('orders/v1/orders/'.$orderNumber, ['seller_contract_id'=>$this->contractId]);

        $response =  json_decode((string) $reponse->getBody(), true);
        return $response['content'];
    }

    final public const PAGINATION = 50;


    public function markOrderAsFulfill($orderId, $carrierCode, $carrierName, $carrierUrl, $trackingNumber):bool
    {
        $products = [];
        $order = $this->getOrder($orderId);
        foreach($order['products'] as $product) {
            $products[] = [
                "seller_sku" =>  $product["seller_sku"],
                "quantity"=>  $product["quantity"]
            ];
        }

        $body = [
            [
                "carrier"=> $carrierCode,
                "order_reference"=>  $orderId,
                "seller_contract_id"=> (int)$this->contractId,
                "tracking_number"=>  $trackingNumber,
                "tracking_url"=> $carrierUrl,
                "products"=>  $products
            ]
        ];
        $reponse =  $this->sendRequest('orders/v1/shippings', [], 'POST', json_encode($body));
        $this->logger->info(json_encode($body));
        if(strlen(json_encode($reponse))>10) {
            throw new Exception("Error during shipping confirmation ".json_encode($reponse));
        }
        return true;
    }

    public function markOrderAsAccepted($order): bool
    {
        $body = [
            [
                "order_reference" => $order['order_reference'],
                "seller_contract_id" => (int)$this->contractId,
            ]
            ];
        $reponse =  $this->sendRequest('orders/v1/accept-orders', [], 'POST', json_encode($body));
        if(!$reponse) {
            throw new Exception("Error during accept confirmation ".json_encode($reponse));
        }
        $this->logger->info('Validated');
        

        return true;
    }



    public function markOrderAsRefused($order): bool
    {
        $body = [
                [
                  "order_reference" => $order['order_reference'],
                  "seller_contract_id" => (int)$this->contractId,
                ]
        ];
        $reponse =  $this->sendRequest('orders/v1/refuse-orders', [], 'POST', json_encode($body));
        if(!$reponse) {
                throw new Exception("Error during accept cancellation ".json_encode($reponse));
        }
        $this->logger->info('Refused');
        return true;
    }


    public function sendStocks($stocks)
    {
      
        $body = [
            'content' => [
                [
                    'seller_contract_id' => (int)$this->contractId,
                    'items' => $stocks
                ]
            ]
          ];
        $this->logger->info(json_encode($body));
        $reponse =  $this->sendRequest('api/v2/offer-information/offers', [], 'PATCH', json_encode($body));
        return json_decode((string) $reponse->getBody(), true);
    }




    public function getAllOffers()
    {
        
        $activeOffers = $this->getOffers("true");
        $this->logger->info("count active ".count($activeOffers));
        $unactiveOffers = $this->getOffers("false");
        $this->logger->info("count unactive ".count($unactiveOffers));
        return array_merge($activeOffers, $unactiveOffers);
    }







    public function getOffers($active)
    {
        $offset = 1;
        $offers = [];
        $continue = true;
        $params = ['seller_contract_id' => (int)$this->contractId, 'offer_online' => (string)$active];
        while ($continue) {
            $params ['page'] = $offset;
            $reponse =  $this->sendRequest('api/v1/offer-information/offers', $params);
            $reponse= json_decode((string) $reponse->getBody(), true);
            $offers = array_merge($offers, $reponse['content']);
            if(count($offers)==$reponse['pagination']['items']) {
                $continue= false;
            } else {
                $this->logger->info('Pagination '.json_encode($reponse['pagination']));
            }
            $offset++;
        }
        
        return $offers;
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
        $this->logger->info('Request '.$url);
        $request = new Request($method, $url, $headers, $body);
        return $client->sendRequest($request);
    }




    public function getCategorieChoices(){
        $categoryIndexed = [];

        $repsonse = $this->sendRequest('api/v1/products/categories');

        $categories =  json_decode((string)  $repsonse->getBody(), true);
        foreach($categories['content'] as $children0){
            $categoryIndexed[$children0['id']] = $children0;
            $this->logger->info('Level 0 '.$children0['level'].' > '.$children0['name'].' '.$children0['id']);

            foreach($children0['children'] as $children1){
                $categoryIndexed[$children1['id']] = $children1;
                $this->logger->info('Level 1 '.$children1['level'].' > '.$children1['name'].' '.$children1['id']);

                foreach($children1['children'] as $children2){
                    $categoryIndexed[$children2['id']] = $children2;
                    $this->logger->info('Level 2 '.$children2['level'].' > '.$children2['name'].' '.$children2['id']);

                    foreach($children2['children'] as $children3){
                        $categoryIndexed[$children3['id']] = $children3;
                        $this->logger->info('Level 3 '.$children3['level'].' > '.$children3['name'].' '.$children3['id']);

                        foreach($children3['children'] as $children4){
                            
                            $this->logger->info('Level 4 '.$children4['level'].' > '.$children4['name'].' '.$children4['id']);
                            $categoryIndexed[$children4['id']] = $children4;


                            foreach($children4['children'] as $children5){
                            
                                $this->logger->info('Level 5 '.$children5['level'].' > '.$children5['name'].' '.$children5['id']);
                                $categoryIndexed[$children5['id']] = $children5;
                            }
                        }
                    }
                }
            }
        }

 
       
        
        $finalCategories = [];
       
        foreach($categoryIndexed as $categoryIndex){
                if($categoryIndex['finalNode']){
                    $this->logger->info("LAst level ".$categoryIndex['id']);
                    $categorie = [
                        'code' => $categoryIndex['id'],
                        'label' => $categoryIndex['names']["en_GB"],
                    ];
               
              

                $path = [];
                
                $categoryCheck = $categoryIndex;
                while($categoryCheck){
                    $this->logger->info("Add path ".$categoryCheck['names']["en_GB"]);
                    $path[] =$categoryCheck['names']["en_GB"];
                    if(array_key_exists($categoryCheck['parentId'], $categoryIndexed)){
                        $categoryCheck = $categoryIndexed[$categoryCheck['parentId']] ;
                    } else {
                        $categoryCheck=false;
                    }
                }
                $pathArray = array_reverse($path);
                $categorie ['path'] = implode(' > ', $pathArray);
                $finalCategories[ $categorie['path']] = $categorie;
                $this->logger->info("finish ".$categorie['path']);
            }
           
        }

        ksort($finalCategories);
        
       
        return $finalCategories;
    }



}
