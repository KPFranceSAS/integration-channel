<?php

namespace App\Service\Lazada;

use App\Entity\WebOrder;
use App\Helper\Api\ApiInterface;
use App\Service\Lazada\LazadaClient;
use Psr\Log\LoggerInterface;

class LazadaApi implements ApiInterface
{
    protected $client;

    protected $logger;

    public function getChannel()
    {
    }


    public function __construct(LoggerInterface $logger, LazadaClient $client)
    {
        $this->client = $client;
        $this->logger = $logger;
    }


    


    /**
     * https://open.proyectoarise.com/apps/doc/api?path=%2Forders%2Fget
     */
    private function getOrders(array $params = [])
    {
        $offset = 0;
        $max_page = 1;
        $orders = [];
        while ($offset  < $max_page) {
            $req = new LazadaRequest('/orders/get', 'GET');
            foreach ($params as $key => $param) {
                $req->addApiParam($key, $param);
            }

            $req->addApiParam('limt', self::PAGINATION);
            $req->addApiParam('offset', $offset);
            $realOffset =  $offset+1;
            $this->logger->info('Get orders batch n°' . $realOffset . ' / ' . $max_page . ' >>' . json_encode($params));
            $reponse = $this->client->execute($req);

            if ($reponse->data->count > 0) {
                $orders = array_merge($orders, $reponse->data->orders);
            }

            $offset+=self::PAGINATION;
            $max_page  = $reponse->data->count;
        }

        return $orders;
    }


    public function getAllOrdersToSend()
    {
        $params = [
            'status' => 'ready_to_ship',
            'created_after' => '2022-09-01T09:00:00+08:00'
        ];
        return $this->getAllOrders($params);
    }


    public function getAllOrders()
    {
        $params = [
            'created_after' => '2022-09-01T09:00:00+08:00'
        ];
        return $this->getOrders($params);
    }



    


    public function getOrder(string $orderNumber)
    {
        $this->logger->info('Get Order  ' . $orderNumber);
        $request = new LazadaRequest('/orders/get', 'GET');
        $request->addApiParam('order_id', $orderNumber);
        $resp = $this->client->execute($request);
        var_dump($resp);
        $order = $resp->result->data;
        

        return ;
    }

    /**
     * https://open.proyectoarise.com/apps/doc/api?path=%2Fproducts%2Fget
     */
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
        while ($offset  < $max_page) {
            $req = new LazadaRequest('/products/get', 'GET');
            foreach ($params as $key => $param) {
                $req->addApiParam($key, $param);
            }

            $req->addApiParam('limit', self::PAGINATION);
            $req->addApiParam('offset', $offset);
            $realOffset =  $offset+1;
            $this->logger->info('Get products batch n°' .$realOffset . ' / ' . $max_page . ' >>' . json_encode($params));
            $reponse = $this->client->execute($req);

            if ($reponse->data->total_products > 0) {
                $products = array_merge($products, $reponse->data->products);
            }

            $offset+=self::PAGINATION;
            $max_page  = $reponse->data->total_products;
        }

        return $products;
    }



    public const PAGINATION = 2;

    

    public function updateStockLevel($productId, $productSku, $inventory)
    {
        $req = new AliexpressSolutionBatchProductInventoryUpdateRequest();
        $mutipleProductUpdateList = new SynchronizeProductRequestDto();
        $mutipleProductUpdateList->product_id = $productId;
        $multipleSkuUpdateList = new SynchronizeSkuRequestDto();
        $multipleSkuUpdateList->sku_code = $productSku;
        $multipleSkuUpdateList->inventory = $inventory;
        $mutipleProductUpdateList->multiple_sku_update_list = $multipleSkuUpdateList;
        $req->setMutipleProductUpdateList(json_encode($mutipleProductUpdateList));
        $this->logger->info('Update Stock Level ' . $productId . ' / SKU ' . $productSku . ' >> ' . $inventory . ' units');
        return $this->client->execute($req, $this->clientAccessToken);
    }



    public function updatePrice($productId, $productSku, $price, $discountPrice = null)
    {
        $req = new AliexpressSolutionBatchProductPriceUpdateRequest();
        $mutipleProductUpdateList = new SynchronizeProductRequestDto();
        $mutipleProductUpdateList->product_id = $productId;
        $multipleSkuUpdateList = new SynchronizeSkuRequestDto();
        $multipleSkuUpdateList->sku_code = $productSku;
        $multipleSkuUpdateList->price = $price;
        if ($discountPrice) {
            $multipleSkuUpdateList->discount_price = $discountPrice;
        } else {
            $multipleSkuUpdateList->discount_price = null;
        }
        $mutipleProductUpdateList->multiple_sku_update_list = $multipleSkuUpdateList;
        $req->setMutipleProductUpdateList(json_encode($mutipleProductUpdateList));
        $this->logger->info('Update price ' . $productId . ' / SKU ' . $productSku . 'regular price >> ' . $price . ' && discount price >>> ' . $discountPrice);
        return $this->client->execute($req, $this->clientAccessToken);
    }


    public function getProductInfo($productId)
    {
        $req = new AliexpressSolutionProductInfoGetRequest();
        $req->setProductId($productId);
        $this->logger->info('Get Product info ' . $productId);
        $reponse = $this->client->execute($req, $this->clientAccessToken);
        return (property_exists($reponse, 'result')) ? $reponse->result : null;
    }




    /*
    public function getCarriers()
    {
        $req = new AliexpressLogisticsRedefiningListlogisticsserviceRequest();
        $resp = $this->client->execute($req, $this->clientAccessToken);
        return $resp->result_list->aeop_logistics_service_result;
    }

    protected function checkIfAlreadySent($orderId)
    {
        try {
            $this->logger->info('Check if already send');
            $order = $this->getOrder($orderId);
            return $order->logistics_status == "SELLER_SEND_GOODS";
        } catch (\Exception $e) {
            $this->logger->info('Exception ' . $e->getMessage());
            return false;
        }
    }


    public function markOrderAsFulfill($orderId, $serviceName, $trackingNumber, $sendType = 'all')
    {
        if (!$this->checkIfAlreadySent($orderId)) {
            $this->logger->info('Order is not marked as sent');
            $req = new AliexpressSolutionOrderFulfillRequest();
            $req->setServiceName($serviceName);
            $req->setOutRef($orderId);
            $req->setSendType($sendType);
            $req->setLogisticsNo($trackingNumber);
            try {
                $result = $this->client->execute($req, $this->clientAccessToken);
                $positive = property_exists($result, 'result')
                            && property_exists($result->result, 'result_success')
                            && $result->result->result_success == true;
                return $positive;
            } catch (\Exception $e) {
                $this->logger->info('Exception ' . $e->getMessage());
                $this->logger->info('result ' . json_encode($result));
                return false;
            }
        } else {
            $this->logger->info('Tracking already sent');
            return true;
        }
    }



    public function getBrandProduct($productId)
    {
        $this->logger->info('Get Band  ' . $productId);
        $productInfo = $this->getProductInfo($productId);
        foreach ($productInfo->aeop_ae_product_propertys->global_aeop_ae_product_property as $property) {
            if ($this->checkIfEgalString($property->attr_name, 'Brand Name')) {
                return $this->cleanString($property->attr_value);
            }
        }
        return null;
    }


    protected function cleanString(string $string)
    {
        return strtoupper(trim(str_replace(' ', '', $string)));
    }


    protected function checkIfEgalString(string $string1, string $string2)
    {
        return $this->cleanString($string1) == $this->cleanString($string2);
    }
    */
}
