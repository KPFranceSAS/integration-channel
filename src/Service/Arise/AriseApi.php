<?php

namespace App\Service\Arise;

use AmazonPHP\SellingPartner\Exception\Exception;
use App\Entity\WebOrder;
use App\Helper\Api\ApiInterface;
use App\Service\Arise\AriseClient;
use Psr\Log\LoggerInterface;

class AriseApi implements ApiInterface
{
    protected $client;

    protected $logger;

    public function getChannel()
    {
        return WebOrder::CHANNEL_ARISE;
    }


    public function __construct(LoggerInterface $logger, AriseClient $client)
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
            $req = new AriseRequest('/orders/get', 'GET');
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



    

    /**
     * https://open.proyectoarise.com/apps/doc/api?path=%2Forder%2Fget
     * https://open.proyectoarise.com/apps/doc/api?path=%2Forder%2Fitems%2Fget
     */
    public function getOrder(string $orderNumber)
    {
        $this->logger->info('Get Order  ' . $orderNumber);
        $request = new AriseRequest('/order/get', 'GET');
        $request->addApiParam('order_id', $orderNumber);
        $resp = $this->client->execute($request);
        $order = $resp->data;
        $this->logger->info('Get Order  lines ' . $orderNumber);
        $subRequest = new AriseRequest('/order/items/get', 'GET');
        $subRequest->addApiParam('order_id', $orderNumber);
        $subResp = $this->client->execute($subRequest);
        $order->lines = $subResp->data;
        return $order;
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
            $req = new AriseRequest('/products/get', 'GET');
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



    public const PAGINATION = 50;

    
    
    public function updateStockLevel($itemId, $skuId, $sellerSku, $inventoryLevel)
    {
        $inventory = [
            'SellableQuantity' => $inventoryLevel,
            'SellerSku' => $sellerSku,
            'SkuId' => $skuId,
            'ItemId' => $itemId
        ];
        return $this->updateStockLevels([$inventory]);
    }






    public function updateStockLevels($inventorys)
    {
        $payload = [
            'Request'=> [
                "Product"=> [
                    "Skus"=> [
                         "Sku" => $inventorys
                         ]
                    ]
            ]
        ];

        $request = new AriseRequest('/product/stock/sellable/update');
        $request->addApiParam('payload', json_encode($payload));
        
        return $this->client->execute($request);
    }

    public function updatePrice($itemId, $skuId, $sellerSku, $price, $salePrice=0)
    {
        $this->logger->info('Send update price '.$sellerSku .' '.$price.' >> '.$salePrice == 0 ? $price : $salePrice);
        $price = [
            'Price' => $price,
            'SalePrice' => $salePrice,
            'SellerSku' => $sellerSku,
            'SkuId' => $skuId,
            'ItemId' => $itemId
        ];
        return $this->updatePrices([$price]);
    }

    public function updatePrices(array $prices)
    {
        $this->logger->info('Send update '.count($prices).' prices');
        $payload = [
            'Request'=> [
                "Product"=> [
                    "Skus"=> [
                        "Sku" => $prices
                    ]
                ]
            ]
        ];

        $request = new AriseRequest('/product/price_quantity/update');
        $request->addApiParam('payload', json_encode($payload));
        
        return $this->client->execute($request);
    }

    public function getProductInfo($itemId)
    {
        $this->logger->info('Get Product info ' . $itemId);
        $request = new AriseRequest('/product/item/get', 'GET');
        $request->addApiParam('item_id', $itemId);
        $reponse = $this->client->execute($request);
        return $reponse->data;
    }


    public function getBrandProduct($productId)
    {
        $this->logger->info('Get Brand  ' . $productId);
        $productInfo = $this->getProductInfo($productId);
        if (property_exists($productInfo, 'attributes') && property_exists($productInfo->attributes, 'brand')) {
            return $productInfo->attributes->brand;
        }
        return null;
    }

    public function createPackForOrder($order)
    {
        $this->logger->info('Create pack for order Id >> '.$order->order_id);
        $orderItemIds = [];
        foreach ($order->lines as $line) {
            if ($line->status == 'pending') {
                $orderItemIds[]=$line->order_item_id;
            }
        }
        $payload = [
            'pack_order_list'=> [
                [
                    "order_item_list"=> $orderItemIds,
                    "order_id"=> $order->order_id
                ]
            ],
            "delivery_type" => "dropship",
            "shipping_allocate_type" =>  "TFS"
        ];

        $request = new AriseRequest('/order/pack');
        $request->addApiParam('packReq', json_encode($payload));
        
        $result = $this->client->execute($request);
        if (property_exists($result->result, 'error_msg')) {
            throw new Exception('Problem on pack creation '. $result->result->error_msg);
        } else {
            foreach ($result->result->data->pack_order_list as $packOrderList) {
                foreach ($packOrderList->order_item_list as $packItemList) {
                    return $packItemList->package_id;
                }
            }
            throw new Exception('No package id '. json_encode($result));
        }
    }

    public function getSeller()
    {
        $this->logger->info('Get seller');
        $request = new AriseRequest('/seller/get', 'GET');
        $reponse = $this->client->execute($request);
        return $reponse->data;
    }

    public function getDbsShipmentProviders()
    {
        $seller = $this->getSeller();
        $this->logger->info('Get Shipment providers');
        $request = new AriseRequest('/order/shipment/sof/providers/get', "GET");
        
        $request->addApiParam('getDBSShipmentProviderReq', json_encode(["sellerId"=> $seller->seller_id]));
        $reponse = $this->client->execute($request);
        return $reponse->result->data->shipment_providers;
    }

    public function getSupplierCode($supplierName) : string
    {
        $suppliers = $this->getDbsShipmentProviders();
        foreach ($suppliers as $supplier) {
            if ($supplier->name == $supplierName) {
                return $supplier->provider_code;
            }
        }
        throw new Exception('Supplier not found '.$supplierName);
    }

    public function updateTrackingInfo($trackingNumber, $packageId, $shipmentProviderCode)
    {
        $this->logger->info('Update TRacking info');
        $request = new AriseRequest('/order/package/tracking/update');

        $tracking= [
           'update_packages'=> [[
                'tracking_number'=> $trackingNumber,
                'package_id'=> $packageId,
                'shipment_provider_code'=> $shipmentProviderCode,
           ]]
        ];
        $request->addApiParam('updateTrackingInfoReq', json_encode($tracking));
        $reponse = $this->client->execute($request);
        return $reponse->result;
    }


    public function markAsReadyToShip($packageId)
    {
        $this->logger->info('Ready to ship');
        $request = new AriseRequest('/order/package/rts');

        $tracking= [
           'packages'=> [[
                'package_id'=> $packageId,
           ]]
        ];
        $request->addApiParam('readyToShipReq', json_encode($tracking));
        $reponse = $this->client->execute($request);
        return $reponse->result;
    }



    public function markAsDelivered($packageId)
    {
        $this->logger->info('Delivered');
        $request = new AriseRequest('/order/package/sof/delivered');

        $tracking= [
           'packages'=> [[
                'package_id'=> $packageId,
           ]]
        ];
        $request->addApiParam("dbsDeliveryReq", json_encode($tracking));
        $reponse = $this->client->execute($request);
        return $reponse->result;
    }


    public function markOrderAsFulfill($orderId, $carrierName, $trackingNumber)
    {
        try {
            $order = $this->getOrder($orderId);
            if ($this->checkIfOrderIsNotMarkedAsShipped($order)) {
                $this->logger->info('Order id marked as sent');

                $packId = $this->createPackForOrder($order);
                $supplierCode = $this->getSupplierCode($carrierName);
                $this->updateTrackingInfo($trackingNumber, $packId, $supplierCode);
                $this->markAsReadyToShip($packId);
                $this->markAsDelivered($packId);
            } else {
                $this->logger->info('Order id already marked as sent');
            }
            return true;
        } catch(Exception $e) {
            $this->logger->critical($e->getMessage());
            return false;
        }
    }


    public function checkIfOrderIsNotMarkedAsShipped($order): bool
    {
        foreach ($order->lines as $line) {
            if ($line->status=='pending') {
                return true;
            }
        }
        return false;
    }
}
