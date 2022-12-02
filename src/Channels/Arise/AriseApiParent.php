<?php

namespace App\Channels\Arise;

use AmazonPHP\SellingPartner\Exception\Exception;
use App\Channels\Arise\AriseClient;
use App\Service\Aggregator\ApiInterface;
use Psr\Log\LoggerInterface;

abstract class AriseApiParent implements ApiInterface
{
    protected $client;

    protected $logger;


    public function __construct(LoggerInterface $logger, $clientId, $clientSecret, $clientAccessToken)
    {
        $this->client = new AriseClient();
        $this->client->addParams($logger, $clientId, $clientSecret, $clientAccessToken);
        $this->logger = $logger;
    }

    public function getClient(): AriseClient
    {
        return $this->client;
    }



    /**
     * https://open.proyectoarise.com/apps/doc/api?path=%2Forders%2Fget
     */
    public function getOrders(array $params = [])
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
            'status' => 'pending',
            'created_after' => '2022-09-01T09:00:00+08:00'
        ];
        return $this->getOrders($params);
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
        $this->logger->info('Send update price '.$sellerSku .' '.$price.' >> '.$salePrice);
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



    


    public function desactivateProduct($itemId, $sellerSku)
    {
        $this->logger->info('Desactivate product '.$sellerSku .' '.$itemId);
        $payload = [
            'Request'=> [
                "Product"=> [
                    "ItemId"=> $itemId,
                    "Skus" => [
                        'SellerSku' => $sellerSku
                    ]
                ]
            ]
        ];

        $request = new AriseRequest('/product/deactivate');
        $request->addApiParam('payload', json_encode($payload));
        
        return $this->client->execute($request);
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
            if (property_exists($line, "package_id") && strlen($line->package_id)) {
                $this->logger->info('Pack already created '.$line->package_id.' for order Id >> '.$order->order_id);
                return $line->package_id;
            }
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
        return $this->client->execute($request);
    }



    public function getDbsShipmentProviders()
    {
        $this->logger->info('Get Shipment providers');
        $seller = $this->getSeller()->data;
        
        $request = new AriseRequest('/order/shipment/sof/providers/get', "GET");
        
        $request->addApiParam('getDBSShipmentProviderReq', json_encode(["sellerId"=> $seller->seller_id]));
        $reponse = $this->client->execute($request);
        return $reponse->result->data->shipment_providers;
    }

    public function getSupplierCode($supplierName) : string
    {
        $this->logger->info('Get supplier code');
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
        $this->logger->info('Ready to ship '.$packageId);
        $request = new AriseRequest('/order/package/rts');

        $tracking= [
           'packages'=> [[
                'package_id'=> $packageId,
           ]]
        ];
        $request->addApiParam('readyToShipReq', json_encode($tracking));
        $reponse = $this->client->execute($request);
        $result = $reponse->result;
        if ($result->success ==true) {
            foreach ($result->data->packages as $package) {
                if ($package->package_id == $packageId && $package->retry == false) {
                    return true;
                }
            }
        }

        return false;
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
       
        $result = $reponse->result;
        if ($result->success ==true) {
            foreach ($result->data->packages as $package) {
                if ($package->package_id == $packageId && $package->retry == false) {
                    return true;
                }
            }
        }

        return false;
    }


    public function getPrintLabel($packageId)
    {
        $this->logger->info('Ask for print label');
        $request = new AriseRequest('/order/package/document/get', 'GET');
        $label= [
            "doc_type" => 'PDF',
            'packages'=> [[
                 'package_id'=> $packageId,
            ]]
         ];
        $request->addApiParam("getDocumentReq", json_encode($label));
        $reponse = $this->client->execute($request);
        return $reponse->result->data->pdf_url;
    }

    public function createLabel($orderId): string
    {
        $order = $this->getOrder($orderId);
        $packId = $this->createPackForOrder($order);
        $label = $this->getPrintLabel($packId);
            
        return $label;
    }







    public function markOrderAsFulfill($orderId, $carrierName, $trackingNumber)
    {
        try {
            $order = $this->getOrder($orderId);
            
            $this->logger->info('Order id marked as sent');

            $packId = $this->createPackForOrder($order);
            $supplierCode = $this->getSupplierCode($carrierName);
            $this->updateTrackingInfo($trackingNumber, $packId, $supplierCode);
            sleep(5);
            $wasMarkAsreday =  $this->markAsReadyToShip($packId);
            if (!$wasMarkAsreday) {
                $this->logger->info('Order was not mark as ready to ship');
                return false;
            }
            $wasMarkAsDelivered = $this->markAsDelivered($packId);
            if (!$wasMarkAsDelivered) {
                $this->logger->info('Order was not mark as delivered');
                return false;
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
