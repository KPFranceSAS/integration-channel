<?php

namespace App\Channels\AliExpress;

use App\Channels\AliExpress\AliExpressClient;
use App\Channels\AliExpress\AliExpressRequest;
use App\Service\Aggregator\ApiInterface;
use Exception;
use Psr\Log\LoggerInterface;
use stdClass;

abstract class AliExpressApiParent implements ApiInterface
{
    protected $clientId;

    protected $clientSecret;

    protected $clientAccessToken;

    protected $client;

    protected $logger;

    abstract public function getChannel();


    public function __construct(LoggerInterface $logger, $clientId, $clientSecret, $clientAccessToken)
    {
        $this->clientAccessToken = $clientAccessToken;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->client = new AliExpressClient();
        $this->client->addParams($logger, $clientId, $clientSecret, $clientAccessToken);
        $this->logger = $logger;
    }




    /**
     * https://developers.aliexpress.com/en/doc.htm?docId=42270&docType=2
     *
     */
    public function getAllOrdersToSend()
    {
        $params = [
            'modified_date_start' => "2021-12-08 00:00:00",
            'order_status' => "WAIT_SELLER_SEND_GOODS",
        ];
        return $this->getAllOrders($params);
    }

    public const PAGINATION = 50;

    /**
     * https://developers.aliexpress.com/en/doc.htm?docId=42270&docType=2
     *
     */
    public function getAllOrders(array $params)
    {
        $current_page = 1;
        $max_page = 1;
        $orders = [];
        while ($current_page  <= $max_page) {
            $req = new AliExpressRequest('aliexpress.solution.order.get');
            $params['page_size'] = self::PAGINATION;
            $params['current_page'] = $current_page;
            $req->addApiParams($params);
            $this->logger->info('Get batch n°' . $current_page . ' / ' . $max_page . ' >>' . json_encode($params));
            $reponse = $this->client->execute($req, $this->clientAccessToken);
            $this->logger->info('Response >>' . json_encode($reponse));
            if(property_exists($reponse, 'aliexpress_solution_order_get_response')){
                if ($reponse->aliexpress_solution_order_get_response->result->total_count > 0) {
                    $orders = array_merge($orders, $reponse->aliexpress_solution_order_get_response->result->target_list->order_dto);
                }
            } else {
                throw new Exception('ALiexpress error on getting orders '. json_encode($reponse));
            }
           

            $current_page++;
            $max_page  = $reponse->aliexpress_solution_order_get_response->result->total_page;
        }

        return $orders;
    }




    public function getAllActiveProducts()
    {
        return $this->getAllProducts(['product_status_type'=>"onSelling"]);
    }





    /**
     * https://developers.aliexpress.com/en/doc.htm?docId=42384&docType=2
     *
     */
    public function getAllProducts(array $params)
    {
        $current_page = 1;
        $max_page = 1;
        $products = [];
        while ($current_page  <= $max_page) {
            $req = new AliExpressRequest('aliexpress.solution.product.list.get');
            $param = new stdClass();
            $param->page_size = self::PAGINATION;
            $param->current_page = $current_page;
            foreach($params as $key=>$value){
                $param->{$key} = $value;
            }
            $req->addApiParam('aeop_a_e_product_list_query', $param);
            $this->logger->info('Get batch n°' . $current_page . ' / ' . $max_page . ' >>' . json_encode($param));
            $reponse = $this->client->execute($req, $this->clientAccessToken);

            if (property_exists($reponse, 'aliexpress_solution_product_list_get_response' ) && $reponse->aliexpress_solution_product_list_get_response->result->product_count > 0) {
                $products = array_merge($products, $reponse->aliexpress_solution_product_list_get_response->result->aeop_a_e_product_display_d_t_o_list->item_display_dto);
            } else {
                $this->logger->error(json_encode($reponse));
            }

            $current_page++;
            $max_page  = $reponse->aliexpress_solution_product_list_get_response->result->total_page;
        }

        return $products;
    }

    /**
     * https://developers.aliexpress.com/en/doc.htm?docId=45135&docType=2
     */
    public function updateStockLevel($productId, $productSku, $inventory)
    {
        $req = new AliExpressRequest('aliexpress.solution.batch.product.inventory.update');
        $mutipleProductUpdateList = ['product_id'=> $productId];
        $mutipleProductUpdateList['multiple_sku_update_list'] = [
            ['inventory'=>$inventory, 'sku_code' =>$productSku ]
        ];
        $req->addApiParam('mutiple_product_update_list', $mutipleProductUpdateList);
        $this->logger->info('Update Stock Level ' . $productId . ' / SKU ' . $productSku . ' >> ' . $inventory . ' units');
        return $this->client->execute($req, $this->clientAccessToken);
    }



    /**
     * https://developers.aliexpress.com/en/doc.htm?docId=45140&docType=2
     */
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



    /**
     * https://developers.aliexpress.com/en/doc.htm?docId=42384&docType=2
     *
     */
    public function getProductInfo($productId)
    {
        $req = new AliExpressRequest('aliexpress.solution.product.info.get');
        $req->addApiParam('product_id', $productId);
        $this->logger->info('Get Product info ' . $productId);
        $reponse = $this->client->execute($req, $this->clientAccessToken);
        return (property_exists($reponse, 'aliexpress_solution_product_info_get_response') 
                && property_exists($reponse->aliexpress_solution_product_info_get_response, 'result')) ? $reponse->aliexpress_solution_product_info_get_response->result : null;
    }



    /**
     * https://developers.aliexpress.com/en/doc.htm?docId=42707&docType=2
     */
    public function getOrder(string $orderNumber)
    {
        $req = new AliExpressRequest('aliexpress.solution.order.info.get');
        $params=new stdClass();
        $params->order_id = $orderNumber;
        $req->addApiParam('param1', $params);
        $this->logger->info('Get Order  ' . $orderNumber);
        $resp = $this->client->execute($req, $this->clientAccessToken);
        if(property_exists($resp, 'aliexpress_solution_order_info_get_response')) {
            return $resp->aliexpress_solution_order_info_get_response->result->data;
        } else {
            throw new Exception('Error Get order '.json_encode($resp));
        }
        
    }



    /**
     * https://developers.aliexpress.com/en/doc.htm?docId=30142&docType=2
     */
    public function getCarriers()
    {
        $req = new AliExpressRequest('aliexpress.logistics.redefining.listlogisticsservice');
        $resp = $this->client->execute($req, $this->clientAccessToken);
        return $resp->aliexpress_logistics_redefining_listlogisticsservice_response->result_list->aeop_logistics_service_result;
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


    /**
     * https://developers.aliexpress.com/en/doc.htm?docId=42269&docType=2
     */
    public function markOrderAsFulfill($orderId, $serviceName, $trackingNumber, $sendType = 'all')
    {
        if (!$this->checkIfAlreadySent($orderId)) {
            $this->logger->info('Order is not marked as sent');
            $req = new AliExpressRequest("aliexpress.solution.order.fulfill");
            $req->addApiParam('service_name', $serviceName);
            $req->addApiParam('out_ref', $orderId);
            $req->addApiParam('send_type', $sendType);
            $req->addApiParam('logistics_no', $trackingNumber);
            try {
                $result = $this->client->execute($req, $this->clientAccessToken);
                $positive = property_exists($result, 'aliexpress_solution_order_fulfill_response') 
                            && property_exists($result->aliexpress_solution_order_fulfill_response, 'result')
                            && property_exists($result->aliexpress_solution_order_fulfill_response->result, 'result_success')
                            && $result->aliexpress_solution_order_fulfill_response->result->result_success == true;
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
     * And replace client_id value by $this->clientId
     *  https://oauth.aliexpress.com/authorize?response_type=code&client_id=XXXXXXX&redirect_uri=https://aliexpress.gadgetiberia.es/es/module/aliexpress_official/auth?token=a1d930a3e4332d2c083978e8b5293b78&state=1212&view=web&sp=ae
     *  in the html request get the code of auth
     *  and use it in the command to regenerate the token. Token is valid for one year.
     */
    public function getNewAccessToken($code)
    {
        $url = 'https://oauth.aliexpress.com/token';
        $postfields = array(
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
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
