<?php

namespace App\Service\OwletCare;

use Exception;
use Psr\Log\LoggerInterface;
use Shopify\Auth\FileSessionStorage;
use Shopify\Clients\Rest;
use Shopify\Context;


class OwletCareApi
{

    private $client;

    private $logger;

    public function __construct(LoggerInterface $logger, $owletCareToken, $owletCareClientId, $owletCareClientSecret, $owletCareShopDomain, $owletCareVersion, $owletCareScopes)
    {
        $this->owletCareToken = $owletCareToken;
        $this->owletCareClientId = $owletCareClientId;
        $this->owletCareClientSecret = $owletCareClientSecret;
        $this->owletCareShopDomain = $owletCareShopDomain;
        $this->owletCareVersion = $owletCareVersion;
        $this->owletCareScopes = $owletCareScopes;

        Context::initialize($owletCareClientId, $owletCareClientSecret, $owletCareScopes, $owletCareShopDomain, new FileSessionStorage('/tmp/php_sessions'), $owletCareVersion);

        $this->client = new Rest($owletCareShopDomain, $owletCareToken);
        $this->logger = $logger;
    }


    public function getShop()
    {
        $response = $this->client->get("shop");
        return $response->getDecodedBody();
    }


    public function getAllOrdersToSend()
    {
        return $this->getAllOrders("open", "paid", "unfulfilled");
    }



    public function getAllOrders($status = 'any', $financialStatus = 'any', $fulfillmentStatus = 'any')
    {
        return $this->getPaginatedElements(
            'orders',
            [
                "status" => $status,
                "financial_status" => $financialStatus,
                'fulfillment_status' => $fulfillmentStatus
            ]
        );
    }


    public function getAllProducts()
    {
        return $this->getPaginatedElements('products');
    }


    public function getAllInventoryLevels($location)
    {
        return $this->getPaginatedElements('inventory_levels', ['location_ids' => $location]);
    }


    public function getLocations()
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
        throw new Exception('No warehouse is active on Owletcare shopify');
    }


    public function getLevelStocksBySku(int $locationId)
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



    public function getAllInventoryLevelsFromProduct()
    {
        $inventoryLevels = [];
        $products = $this->getAllProducts();
        foreach ($products as $product) {
            foreach ($product['variants'] as $variant) {
                $inventoryLevels[] = [
                    'sku' => $variant['inventory_item_id'],
                    'inventory_item_id' => $variant['inventory_item_id']
                ];
            }
        }
        return $inventoryLevels;
    }







    public function getInventoryItems(array $inventoryItemIds)
    {
        return $this->getPaginatedElements('inventory_items', [], ['ids' => implode(',', $inventoryItemIds)]);
    }


    public function getInventoryLevels($locationId)
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









    private function extractLinkNext($links)
    {
        if ($links == null) {
            return null;
        }
        $link = $links[0];
        if (strpos($link, 'rel="next"') === false) {
            return null;
        }
        $tobeReplace = ["<", ">", 'rel="next"', ";", 'rel="previous"'];
        $tobeReplaceWith = ["", "", "", ""];
        parse_str(parse_url(str_replace($tobeReplace, $tobeReplaceWith, $link), PHP_URL_QUERY), $op);
        return trim($op['page_info']);
    }


    private function getPaginatedElements($endPoint, $headers = [], $query = [])
    {
        $nextToken = null;
        $elements = [];
        do {
            if ($nextToken) {
                $query['page_info'] = $nextToken;
            }
            $response = $this->client->get(
                $endPoint,
                $headers,
                $query
            );
            $responseArray = $response->getDecodedBody();
            $this->logger->info('Fetch ' . count($responseArray[$endPoint]) . " new elements");
            $elements = array_merge($elements, $responseArray[$endPoint]);
            $nextToken = $this->extractLinkNext($response->getHeader('Link'));
        } while ($nextToken != null);
        return $elements;
    }
}
