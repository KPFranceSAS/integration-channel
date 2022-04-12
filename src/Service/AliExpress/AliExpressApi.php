<?php

namespace App\Service\AliExpress;

use AliexpressLogisticsRedefiningListlogisticsserviceRequest;
use AliexpressSolutionBatchProductInventoryUpdateRequest;
use AliexpressSolutionOrderFulfillRequest;
use AliexpressSolutionOrderGetRequest;
use AliexpressSolutionOrderInfoGetRequest;
use AliexpressSolutionProductInfoGetRequest;
use AliexpressSolutionProductListGetRequest;
use ItemListQuery;
use OrderDetailQuery;
use OrderQuery;
use Psr\Log\LoggerInterface;
use SynchronizeProductRequestDto;
use SynchronizeSkuRequestDto;
use TopClient;

class AliExpressApi
{


    protected $aliExpressClientId;

    protected $aliExpressClientSecret;

    protected $aliExpressClientAccessToken;

    protected $client;

    protected $logger;


    public function __construct(LoggerInterface $logger, $aliExpressClientId, $aliExpressClientSecret, $aliExpressClientAccessToken)
    {
        $this->aliExpressClientAccessToken = $aliExpressClientAccessToken;
        $this->aliExpressClientId = $aliExpressClientId;
        $this->aliExpressClientSecret = $aliExpressClientSecret;
        $this->client = new TopClient($this->aliExpressClientId, $this->aliExpressClientSecret);
        $this->client->format = 'json';
        $this->logger = $logger;
    }


    /**
     * https://developers.aliexpress.com/en/doc.htm?docId=42270&docType=2
     *
     */
    public function getOrdersToSend()
    {
        $param0 = new OrderQuery();
        $param0->modified_date_start = "2021-12-08 00:00:00";
        $param0->order_status = "WAIT_SELLER_SEND_GOODS";
        return $this->getAllOrders($param0);
    }

    const PAGINATION = 50;

    /**
     * https://developers.aliexpress.com/en/doc.htm?docId=42270&docType=2
     *
     */
    public function getAllOrders(OrderQuery $param)
    {
        $current_page = 1;
        $max_page = 1;
        $orders = [];
        while ($current_page  <= $max_page) {
            $req = new AliexpressSolutionOrderGetRequest();
            $param->page_size = self::PAGINATION;
            $param->current_page = $current_page;
            $req->setParam0(json_encode($param));
            $this->logger->info('Get batch n°' . $current_page . ' / ' . $max_page . ' >>' . json_encode($param));
            $reponse = $this->client->execute($req, $this->aliExpressClientAccessToken);

            if ($reponse->result->total_count > 0) {
                $orders = array_merge($orders, $reponse->result->target_list->order_dto);
            }

            $current_page++;
            $max_page  = $reponse->result->total_page;
        }

        return $orders;
    }




    public function getAllActiveProducts()
    {
        $productQuery = new ItemListQuery();
        $productQuery->product_status_type = "onSelling";
        return $this->getAllProducts($productQuery);
    }





    /**
     * https://developers.aliexpress.com/en/doc.htm?docId=42384&docType=2
     *
     */
    public function getAllProducts(ItemListQuery $param)
    {
        $current_page = 1;
        $max_page = 1;
        $products = [];
        while ($current_page  <= $max_page) {
            $req = new AliexpressSolutionProductListGetRequest();
            $param->page_size = self::PAGINATION;
            $param->current_page = $current_page;
            $req->setAeopAEProductListQuery(json_encode($param));
            $this->logger->info('Get batch n°' . $current_page . ' / ' . $max_page . ' >>' . json_encode($param));
            $reponse = $this->client->execute($req, $this->aliExpressClientAccessToken);

            if ($reponse->result->product_count > 0) {
                $products = array_merge($products, $reponse->result->aeop_a_e_product_display_d_t_o_list->item_display_dto);
            }

            $current_page++;
            $max_page  = $reponse->result->total_page;
        }

        return $products;
    }

    /**
     * https://developers.aliexpress.com/en/doc.htm?docId=45135&docType=2
     */
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
        return $this->client->execute($req, $this->aliExpressClientAccessToken);
    }



    /**
     * https://developers.aliexpress.com/en/doc.htm?docId=42384&docType=2
     *
     */
    public function getProductInfo($productId)
    {
        $req = new AliexpressSolutionProductInfoGetRequest();
        $req->setProductId($productId);
        $this->logger->info('Get Product info ' . $productId);
        $reponse = $this->client->execute($req, $this->aliExpressClientAccessToken);
        return (property_exists($reponse, 'result')) ? $reponse->result : null;
    }



    /**
     * https://developers.aliexpress.com/en/doc.htm?docId=42707&docType=2
     */
    public function getOrder(string $orderNumber)
    {
        $req = new AliexpressSolutionOrderInfoGetRequest();
        $param1 = new OrderDetailQuery();
        $param1->order_id = $orderNumber;
        $req->setParam1(json_encode($param1));
        $this->logger->info('Get Order  ' . $orderNumber);
        $resp = $this->client->execute($req, $this->aliExpressClientAccessToken);
        return $resp->result->data;
    }



    /**
     * https://developers.aliexpress.com/en/doc.htm?docId=30142&docType=2
     */
    public function getCarriers()
    {
        $req = new AliexpressLogisticsRedefiningListlogisticsserviceRequest();
        $resp = $this->client->execute($req, $this->aliExpressClientAccessToken);
        return $resp->result_list->aeop_logistics_service_result;
    }

    protected  function checkIfAlreadySent($orderId)
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


    /**
     * https://developers.aliexpress.com/en/doc.htm?docId=42269&docType=2
     */
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
                $result = $this->client->execute($req, $this->aliExpressClientAccessToken);
                $positive = property_exists($result, 'result') && property_exists($result->result, 'result_success') && $result->result->result_success == true;
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


    /**
     * go on your brower with the master account of aliexpress
     * And replace client_id value by $this->aliExpressClientId
     *  https://oauth.aliexpress.com/authorize?response_type=code&client_id=XXXXXXX&redirect_uri=https://aliexpress.gadgetiberia.es/es/module/aliexpress_official/auth?token=a1d930a3e4332d2c083978e8b5293b78&state=1212&view=web&sp=ae
     *  in the html request get the code of auth and use it in the command to regenerate the token. Token is valid for one year.
     */
    public function  getNewAccessToken($code)
    {
        $url = 'https://oauth.aliexpress.com/token';
        $postfields = array(
            'grant_type' => 'authorization_code',
            'client_id' => $this->aliExpressClientId,
            'client_secret' => $this->aliExpressClientSecret,
            'code' => $code,
            'sp' => 'ae',
            'redirect_uri' => 'http://www.oauth.net/2/'
        );
        $post_data = '';


        foreach ($postfields as $key => $value) {
            $post_data .= "$key=" . urlencode($value) . "&";
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        curl_setopt($ch, CURLOPT_POST, true);


        curl_setopt($ch, CURLOPT_POSTFIELDS, substr($post_data, 0, -1));
        $output = curl_exec($ch);
        curl_close($ch);
        $reponse = json_decode($output, true);
        return $reponse;
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
}
