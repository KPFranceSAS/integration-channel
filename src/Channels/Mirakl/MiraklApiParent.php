<?php

namespace App\Channels\Mirakl;

use App\Service\Aggregator\ApiInterface;
use Exception;
use GuzzleHttp\Client;
use Mirakl\Core\Domain\Collection\DocumentCollection;
use Mirakl\Core\Domain\Document;
use Mirakl\MCI\Common\Domain\Product\ProductImportTracking;
use Mirakl\MCI\Shop\Client\ShopApiClient;
use Mirakl\MCI\Shop\Request\Product\ProductImportRequest;
use Mirakl\MMP\Common\Domain\Order\Accept\AcceptOrderLine;
use Mirakl\MMP\OperatorShop\Request\Message\GetThreadDetailsRequest;
use Mirakl\MMP\OperatorShop\Request\Message\GetThreadsRequest;
use Mirakl\MMP\Shop\Request\Offer\GetOffersRequest;
use Mirakl\MMP\Shop\Request\Offer\UpdateOffersRequest;
use Mirakl\MMP\Shop\Request\Order\Accept\AcceptOrderRequest;
use Mirakl\MMP\Shop\Request\Order\Document\UploadOrdersDocumentsRequest;
use Mirakl\MMP\Shop\Request\Order\Get\GetOrdersRequest;
use Mirakl\MMP\Shop\Request\Order\Ship\ShipOrderRequest;
use Mirakl\MMP\Shop\Request\Order\Tracking\UpdateOrderTrackingInfoRequest;
use Psr\Log\LoggerInterface;
use SplFileObject;
use Symfony\Component\Filesystem\Filesystem;

abstract class MiraklApiParent implements ApiInterface
{
    protected $client;

    protected $logger;


    protected $clientUrl;


    protected $clientKey;

    protected $shopId;

    protected $projectDir;


    public function __construct(LoggerInterface $logger, string $projectDir, string $clientUrl, string $clientKey, ?string $shopId=null)
    {
        $this->client = new ShopApiClient($clientUrl, $clientKey, $shopId);
        $this->client->setLogger($logger);
        $this->projectDir =  $projectDir.'/var/invoices/';
        $this->logger = $logger;
        $this->clientUrl = $clientUrl;
        $this->clientKey = $clientKey;
        $this->shopId = $shopId;
    }

    public function getClient(): ShopApiClient
    {
        return $this->client;
    }



    public function getMessage($idMessage)
    {
        $request = new GetThreadDetailsRequest($idMessage);
        return $this->client->getThreadDetails($request);
    }


    /**
         * Summary of GetOrdersRequest
         * @param array $params
         * @return array
         */
    public function getMessages(array $params = [])
    {
        $continue = true;
        $orders = [];
        $nextToken = null;
        $realOffset = 1;
        while ($continue) {
            $req = new GetThreadsRequest();
            foreach ($params as $key => $param) {
                $req->setData($key, $param);
            }

            $req->setMax(self::PAGINATION);
            if($nextToken) {
                $req->setPageToken($nextToken);
            }
            
           
            $this->logger->info('Get threads batch n°' . $realOffset .  ' >>' . json_encode($params));
            $reponse = $this->client->getThreads($req);
            if (count($reponse->getCollection()->getItems()) > 0) {
                $orders = array_merge($orders, $reponse->getCollection()->getItems());
            }
            $realOffset++;
            if($reponse->getNextPageToken()) {
                $nextToken = $reponse->getNextPageToken();
            } else {
                $continue = false;
            }
            
        }
        $ordersSanitized = [];
        foreach ($orders as $order) {
            $ordersSanitized[]=$order->toArray();
        }
        return $ordersSanitized;
    }

    
    /**
     * Summary of GetOrdersRequest
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
        $ordersSanitized = [];
        foreach ($orders as $order) {
            $ordersSanitized[]=$order->toArray();
        }
        return $ordersSanitized;
    }


    public function getAllOrdersToSend()
    {
        $params = [
            'order_states' => [
                "SHIPPING"
            ]
        ];
        return $this->getOrders($params);
    }




    /**
     * Summary of GetOrdersRequest
     * @param array $params
     * @return array
     */
    public function getOffers(array $params = [])
    {
        $offset = 0;
        $max_page = 1;
        $orders = [];
        while ($offset  < $max_page) {
            $req = new GetOffersRequest($this->shopId);
            foreach ($params as $key => $param) {
                $req->setData($key, $param);
            }

            $req->setMax(self::PAGINATION);
            $req->setOffset($offset);
            $realOffset =  $offset+1;
            $this->logger->info('Get offers batch n°' . $realOffset . ' / ' . $max_page . ' >>' . json_encode($params));
            $reponse = $this->client->getOffers($req);
            if (count($reponse->getItems()) > 0) {
                $orders = array_merge($orders, $reponse->getItems());
            }
            $offset+=self::PAGINATION;
            $max_page  = $reponse->getTotalCount();
        }
        $ordersSanitized = [];
        foreach ($orders as $order) {
            $ordersSanitized[]=$order->toArray();
        }
        return $ordersSanitized;
    }





    public function getAllOrdersToAccept()
    {
        $params = [
            'order_states' => [
                'WAITING_ACCEPTANCE',
            ]
        ];
        return $this->getOrders($params);
    }



    

   
    public function getOrder(string $orderNumber)
    {
        $this->logger->info('Get Order  ' . $orderNumber);
        return;
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

    


    public function sendInvoice($orderId, $invoiceNumber, $invoiceContent)
    {
        $docs = new DocumentCollection();
        $fs = new Filesystem();
        $filename= 'invoice_'.str_replace("/", '_', $invoiceNumber).'_'.date('YmdHis').'.pdf';
        $filePath = $this->projectDir.$filename;
        $fs->dumpFile($filePath, $invoiceContent);
        $file = new SplFileObject($filePath);

        $docs->add(new Document($file, $filename, 'CUSTOMER_INVOICE'));
        $request = new UploadOrdersDocumentsRequest($docs, $orderId);
        $result = $this->client->uploadOrderDocuments($request);
        $fs->remove($filePath);
        
        return true;
    }
   

    





    public function markOrderAsFulfill($orderId, $carrierCode, $carrierName, $carrierUrl, $trackingNumber):bool
    {
        $request = new UpdateOrderTrackingInfoRequest($orderId, [
                'carrier_code' => $carrierCode,
                'carrier_name' => $carrierName,
                'carrier_url' => $carrierUrl,
                'tracking_number' => $trackingNumber,
             ]);
        $result = $this->client->updateOrderTrackingInfo($request);


        $request = new ShipOrderRequest($orderId);
        $result = $this->client->shipOrder($request);
        return true;
    }



    public function markOrderAsAcceptedRefused($order, $accepted): bool
    {
        $ordersId = [];
        foreach ($order['order_lines'] as $orderLine) {
            if ($orderLine["status"]['state']=='WAITING_ACCEPTANCE') {
                $ordersId[] =  new AcceptOrderLine(['id' => $orderLine['id'], 'accepted' => $accepted]);
            }
        }
        if (count($ordersId)>0) {
            $request = new AcceptOrderRequest($order['id'], $ordersId);
            $this->client->acceptOrder($request);
            return true;
        } else {
            return false;
        }
    }


    public function markOrderAsAccepted($order): bool
    {
        return $this->markOrderAsAcceptedRefused($order, true);
    }


    public function markOrderAsRefused($order): bool
    {
        return $this->markOrderAsAcceptedRefused($order, false);
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
