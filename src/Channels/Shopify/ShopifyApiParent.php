<?php

namespace App\Channels\Shopify;

use App\Service\Aggregator\ApiInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Shopify\Auth\FileSessionStorage;
use Shopify\Clients\Rest;
use Shopify\Context;

abstract class ShopifyApiParent implements ApiInterface
{
    protected $client;

    protected $logger;

    protected $shopifyShopDomain;

    protected $shopifyToken;

    abstract public function getChannel();


    public function __construct(
        LoggerInterface $logger,
        $shopifyToken,
        $shopifyClientId,
        $shopifyClientSecret,
        $shopifyShopDomain,
        $shopifyVersion,
        $shopifyScopes
    ) {
        $this->shopifyToken = $shopifyToken;
        $this->shopifyShopDomain = $shopifyShopDomain;

        Context::initialize(
            $shopifyClientId,
            $shopifyClientSecret,
            $shopifyScopes,
            $shopifyShopDomain,
            new FileSessionStorage('/tmp/php_sessions'),
            $shopifyVersion,
        );

        $this->client = new Rest($this->shopifyShopDomain, $this->shopifyToken);
        $this->logger = $logger;
    }


    public function getShop()
    {
        $response = $this->client->get("shop");
        return $response->getDecodedBody();
    }


    public function getAllOrdersToSend(): array
    {
        return $this->getAllOrders("open", "paid", "unfulfilled");
    }


    public function getAllOrders($status = 'any', $financialStatus = 'any', $fulfillmentStatus = 'any'): array
    {
        return $this->getPaginatedElements(
            'orders',
            [],
            [
                "status" => $status,
                "financial_status" => $financialStatus,
                'fulfillment_status' => $fulfillmentStatus
            ]
        );
    }



    public function getAllShopifyPaiements(): array
    {
        return $this->getPaginatedElements(
            "shopify_payments/balance/transactions",
            [],
            [],
            'transactions'
        );
    }



    public function getAllTransactions(string $orderNumber): array
    {
        return $this->getPaginatedElements(
            "orders/$orderNumber/transactions",
            [],
            [
                "order_id" => $orderNumber,
            ],
            'transactions'
        );
    }


    public function getOrder(string $orderNumber)
    {
        return $this->getPaginatedElements(
            'orders',
            [],
            [
                "number" => $orderNumber,
            ]
        );
    }
    


    public function getAllProducts(): array
    {
        return $this->getPaginatedElements('products');
    }


    public function getAllInventoryLevels($location): array
    {
        return $this->getPaginatedElements('inventory_levels', [], ['location_ids' => $location]);
    }


    public function getLocations(): array
    {
        return $this->getPaginatedElements('locations');
    }


    public function getMainLocation()
    {
        $locations = $this->getPaginatedElements('locations');
        foreach ($locations as $location) {
            if ($location['active'] == true) {
                return $location;
            }
        }
        throw new Exception('No warehouse is active on ' . $this->shopifyShopDomain . ' shopify');
    }


    public function getLevelStocksBySku(int $locationId): array
    {
        $inventoryItemIds = [];
        $inventoryLevels = $this->getInventoryLevels($locationId);
        foreach ($inventoryLevels as $inventoryLevel) {
            $inventoryItemIds[] =  $inventoryLevel['inventory_item_id'];
        }
        $inventoryItems =  $this->getInventoryItems($inventoryItemIds);

        $stockBySkus = [];
        foreach ($inventoryItems as $inventoryItem) {
            foreach ($inventoryLevels as $inventoryLevel) {
                if ($inventoryLevel['inventory_item_id'] == $inventoryItem['id']) {
                    $stockBySkus[$inventoryItem["sku"]] = $inventoryLevel['available'];
                }
            }
        }
        return $stockBySkus;
    }



    public function getAllInventoryLevelsFromProduct(): array
    {
        $inventoryLevels = [];
        $products = $this->getAllProducts();
        foreach ($products as $product) {
            foreach ($product['variants'] as $variant) {
                $inventoryLevels[] = [
                    'sku' => $variant['sku'],
                    'inventory_item_id' => $variant['inventory_item_id']
                ];
            }
        }
        return $inventoryLevels;
    }

    public function getInventoryItems(array $inventoryItemIds): array
    {
        return $this->getPaginatedElements('inventory_items', [], ['ids' => implode(',', $inventoryItemIds)]);
    }


    public function getInventoryLevels($locationId): array
    {
        return $this->getPaginatedElements('inventory_levels', [], ['location_ids' => $locationId]);
    }

    public function setInventoryLevel(int $locationId, int $inventoryItemId, int $avalaible)
    {
        return $this->client->post(
            "inventory_levels/set",
            [
                "location_id" =>  $locationId,
                "inventory_item_id" => $inventoryItemId,
                "available" => $avalaible
            ]
        );
    }


    public function markAsFulfilled(
        int $orderId,
        int $locationId,
        array $itemLineId,
        string $trackingNumber = null,
        string $trackingUrl = null
    ) {
        $params = [
            "location_id" =>  $locationId,
            "line_items" => $itemLineId
        ];

        if ($trackingNumber) {
            $params['tracking_number'] = $trackingNumber;
        }

        if ($trackingUrl) {
            $params['tracking_url'] = $trackingUrl;
        }

        return $this->client->post(
            "orders/$orderId/fulfillments",
            ["fulfillment" => $params]
        );
    }


    protected function extractLinkNext($links)
    {
        if ($links == null) {
            return null;
        }
        $link = $links[0];
        if (strpos($link, 'rel="next"') === false) {
            return null;
        }

        if (strpos($link, 'rel="previous"') !== false) {
            $linkPart = explode('rel="previous"', $link, 2);
            $link = $linkPart[1];
        }
        $tobeReplace = ["<", ">", 'rel="next"', ";", 'rel="previous"'];
        $tobeReplaceWith = ["", "", "", ""];
        parse_str(parse_url(str_replace($tobeReplace, $tobeReplaceWith, $link), PHP_URL_QUERY), $op);
        return trim($op['page_info']);
    }


    protected function getPaginatedElements($endPoint, $headers = [], $query = [], $associativeIndex =null): array
    {
        $nextToken = null;
        $elements = [];

        $indexKey = $associativeIndex ? $associativeIndex : $endPoint;
        do {
            if ($nextToken) {
                $query = [];
                $query['page_info'] = $nextToken;
            }
            $response = $this->client->get(
                $endPoint,
                $headers,
                $query
            );
            $responseArray = $response->getDecodedBody();
            if (array_key_exists($indexKey, $responseArray)) {
                $this->logger->info('Fetch ' . count($responseArray[$indexKey ]) . " new elements");
                $elements = array_merge($elements, $responseArray[$indexKey ]);
                $this->logger->info('Fetch ' . count($elements) . " elements");
                
                $nextToken = $this->extractLinkNext($response->getHeader('Link'));
            } else {
                throw new Exception('Error get '.$endPoint.' on Shopify '.$responseArray['errors']);
            }
        } while ($nextToken != null);
        return $elements;
    }



    public function createProduct(array $product){
       return $this->client->post('products', ['product' => $product]);
    }

    public function updateProduct($idProduct, array $product){
        return $this->client->put('products/'.$idProduct, ['product' => $product]);
    }


    public function createImagesProduct($idProduct, array $productImages){
        return $this->client->post('products/'.$idProduct.'/images', ['image' => $productImages]);
     }

    public function createVariantProduct($idProduct, array $productVariant){
        return $this->client->post('products/'.$idProduct.'/variants', ['variant' => $productVariant]);
     }
 
     public function updateProductVariant($idVariant, array $productVariant){
         return $this->client->put('variants/'.$idVariant, ['variant' => $productVariant]);
     }
            


     
 
    


}
