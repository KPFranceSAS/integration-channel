<?php

namespace App\Channels\Mirakl;

use App\Channels\Mirakl\MiraklClient;
use App\Service\Aggregator\ApiInterface;
use Exception;
use Mirakl\MCI\Shop\Client\ShopApiClient;
use Psr\Log\LoggerInterface;

abstract class MiraklApiParent implements ApiInterface
{
    protected $client;

    protected $logger;


    public function __construct(LoggerInterface $logger, $clientUrl, $clientKey, $shopId=null)
    {
        $this->client = new ShopApiClient($clientUrl, $clientKey, $shopId);
        $this->client->setLogger($logger);
        
        $this->logger = $logger;
    }

    public function getClient(): ShopApiClient
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


    public function migrateImage($url)
    {
    }



    public function uploadImage($content)
    {
    }
}
