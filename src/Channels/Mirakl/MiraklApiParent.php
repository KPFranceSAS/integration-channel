<?php

namespace App\Channels\Mirakl;

use App\Channels\Mirakl\MiraklClient;
use App\Service\Aggregator\ApiInterface;
use Exception;
use GuzzleHttp\Client;
use Mirakl\MCI\Common\Domain\Product\ProductImportTracking;
use Mirakl\MCI\Shop\Client\ShopApiClient;
use Mirakl\MCI\Shop\Request\Product\ProductImportRequest;
use Mirakl\MMP\Shop\Request\Offer\UpdateOffersRequest;
use Mirakl\MMP\Shop\Request\Order\Get\GetOrdersRequest;
use Psr\Log\LoggerInterface;
use SplFileObject;

abstract class MiraklApiParent implements ApiInterface
{
    protected $client;

    protected $logger;


    protected $clientUrl;


    protected $clientKey;

    protected $shopId;


    public function __construct(LoggerInterface $logger, string $clientUrl, string $clientKey, ?string $shopId=null)
    {
        $this->client = new ShopApiClient($clientUrl, $clientKey, $shopId);
        $this->client->setLogger($logger);
        
        $this->logger = $logger;
        $this->clientUrl = $clientUrl;
        $this->clientKey = $clientKey;
        $this->shopId = $shopId;
    }

    public function getClient(): ShopApiClient
    {
        return $this->client;
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
        $offset = 0;
        $max_page = 1;
        $orders = [];
        while ($offset  < $max_page) {
            $req = new GetOrdersRequest();
            foreach ($params as $key => $param) {
                $req->setData($key, $param);
            }

            $req->setMax(self::PAGINATION);
            $req->setOffset($offset);
            $realOffset =  $offset+1;
            $this->logger->info('Get orders batch n°' . $realOffset . ' / ' . $max_page . ' >>' . json_encode($params));
            $reponse = $this->client->getOrders($req);
            if (count($reponse->getItems()) > 0) {
                $orders = array_merge($orders, $reponse->getItems());
            }
            $offset+=self::PAGINATION;
            $max_page  = $reponse->getTotalCount();
        }
        return $orders;
    }


    public function getAllOrdersToSend()
    {
        $params = [
            
        ];
        return $this->getOrders($params);
    }


    public function getAllOrders()
    {
        $params = [
           
        ];
        return $this->getOrders($params);
    }

    public function getAllOrdersReadyToShip()
    {
        $params = [
          
        ];
        return $this->getOrders($params);
    }

    public function getAllOrdersShipping()
    {
        $params = [
            
        ];
        return $this->getOrders($params);
    }

    public function getAllOrdersDelivered()
    {
        $params = [
            
        ];
        return $this->getOrders($params);
    }
    
    

   
    public function getOrder(string $orderNumber)
    {
        $this->logger->info('Get Order  ' . $orderNumber);
        


        return;
    }

    public function getAllActiveProducts()
    {
        $params=[
            'filter' => 'live'
        ];
        return $this->getProducts($params);
    }


    public function getAllProducts()
    {
        return $this->getProducts();
    }


    public function getProducts(array $params = [])
    {
        $offset = 0;
        $max_page = 1;
        $products = [];
        

        return $products;
    }


    public function sendOfferImports(array  $offers)
    {
            $request = new UpdateOffersRequest();
            $request->setOffers($offers);

            $result = $this->client->updateOffers($request);
            return $result;
         
    }


    public function sendProductImports(string $file): ProductImportTracking
    {
        $request = new ProductImportRequest(new SplFileObject($file));
        $request->setOperatorFormat(true);
        $result = $this->client->importProducts($request);
        return $result;
    }


    public const PAGINATION = 50;

    

   


    public function updateStockLevel()
    {
    }






    public function updateStockLevels($inventorys)
    {
    }

    public function updatePrice($itemId, $skuId, $sellerSku, $price, $salePrice=0)
    {
    }

    public function updatePrices(array $prices)
    {
    }

    public function getProductInfo($itemId)
    {
    }



    


    public function desactivateProduct($itemId, $sellerSku)
    {
    }



    public function updateTrackingInfo($trackingNumber, $packageId, $shipmentProviderCode)
    {
    }





    public function markOrderAsFulfill($orderId, $carrierName, $trackingNumber)
    {
    }




   
    public function createProduct($product)
    {
    }


    public function updateProduct($product)
    {
    }

    


    public function getAllAttributesForCategory($hierarchyCode)
    {
        $params = [
            'hierarchy' => $hierarchyCode,
            'max_level' => 0,
            'all_operator_attributes' => "true"
        ];
        return $this->sendRequest('products/attributes', $params);
    }


    public function getAllAttributes()
    {
        return $this->sendRequest('products/attributes', [
            'all_operator_attributes' => "true"
        ]);
    }



    public function getAllAttributesValueForCode($code)
    {
        $params = [
            'code' => $code,
        ];
        return $this->sendRequest('values_lists', $params);
    }


    public function getAllAttributesValue()
    {
        return $this->sendRequest('values_lists');
    }





    public function sendRequest($endPoint, $queryParams = [], $method = 'GET', $form = null)
    {
        $parameters = [
            'query' => $queryParams,
            'debug' => true,
            'headers' => [
                    "Authorization"=>$this->clientKey
                    ]
        ];
        if ('GET' != $method && $form) {
            $parameters['json'] = $form;
        }
        $client = new Client();
        $response = $client->request($method, $this->clientUrl."/". $endPoint, $parameters);

        return json_decode($response->getBody());
    }
}
