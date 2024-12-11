<?php

namespace App\Channels\Amazon;

use AmazonPHP\SellingPartner\Model\FulfillmentInbound\Region;
use AmazonPHP\SellingPartner\Regions;
use App\Service\Aggregator\ApiInterface;
use DateInterval;
use DateTime;
use Psr\Log\LoggerInterface;

abstract class AmazonApiParent implements ApiInterface
{
    
    public function __construct(private readonly LoggerInterface $logger, private AmazonApiClient $amazonApiClient)
    {
       
    }



    public function getSdk()
    {
        return $this->amazonApiClient->getSdk();
    }


    public function getSdkOrders()
    {
        return $this->getSdk()->orders();
    }
    

    abstract protected function getMarketplaceId();


    public function getAccessToken()
    {
        return $this->amazonApiClient->getAccessToken();
    }


    public function getRestrictedToken()
    {
        return $this->amazonApiClient->getAccessToken();
        return $this->amazonApiClient->getRestrictedToken();
    }


   
    public function getOrders(string $status, string $channel, DateTime $updatedAfter= null): array
    {
        $dateTimeUpdateAfter = $updatedAfter ? $this->formateDate($updatedAfter) : $this->formateDate((new DateTime())->sub(new DateInterval('P1D')));
        $orders = [];
        $nextToken = null;
        while (true) {
            $response =  $this->getSdkOrders()->getOrders(
                $this->getRestrictedToken(),
                Regions::EUROPE,
                [$this->getMarketplaceId()],
                null,
                null,
                $dateTimeUpdateAfter,
                null,
                [$status],
                [$channel],
                null,
                null,
                null,
                null,
                null,
                null,
                $nextToken
            );
            
            $orderList = $response->getPayload();
            $orders = array_merge( $orders, $this->arraytoArray($orderList->getOrders()));
            
            if ($orderList->getNextToken()) {
                $nextToken = $orderList->getNextToken();
            } else {
                return  $orders;
            }
        }


        return $orders;
    }

    

    protected function arraytoArray($elements){
        $transformed = [];
        foreach($elements as $element){
            $transformed[]= json_decode($element->jsonSerialize(), true);
        }
        return $transformed;
     }

    
    protected function formateDate(DateTime $date)
    {
        return $date->format('Y-m-d\TH:i:s\Z');
    }




    public function getOrderItems($orderNumber)
    {
        $response = $this->getSdkOrders()->getOrderItems(
            $this->getRestrictedToken(),
            Regions::EUROPE,
            $orderNumber
        );
        return $this->arraytoArray($response->getPayload()->getOrderItems());
    }


    

    public function getAllOrdersToSend()
    {
        return $this->getOrders('Unshipped', 'MFN');
    }



    public function getAllOrdersToInvoice()
    {
        return $this->getOrders('Shipped', 'AFN');
    }


   
    public function getOrder(string $orderNumber)
    {
        return $this->getSdkOrders()->getOrder($this->getRestrictedToken(), Regions::EUROPE, $orderNumber);
    }



    public function markOrderAsFulfill($orderId, $carrierCode, $carrierName, $carrierUrl, $trackingNumber):bool
    {
        
        return true;
    }
    

}
