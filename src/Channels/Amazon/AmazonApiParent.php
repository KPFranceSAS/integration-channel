<?php

namespace App\Channels\Amazon;

use AmazonPHP\SellingPartner\Model\Feeds\CreateFeedDocumentSpecification;
use AmazonPHP\SellingPartner\Model\Feeds\CreateFeedSpecification;
use AmazonPHP\SellingPartner\Model\FulfillmentInbound\Region;
use AmazonPHP\SellingPartner\Model\Orders\ConfirmShipmentOrderItem;
use AmazonPHP\SellingPartner\Model\Orders\ConfirmShipmentRequest;
use AmazonPHP\SellingPartner\Model\Orders\PackageDetail;
use AmazonPHP\SellingPartner\Regions;
use App\Service\Aggregator\ApiInterface;
use DateInterval;
use DateTime;
use GuzzleHttp\Client;
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


    public function getSdkFeeds()
    {
        return $this->getSdk()->feeds();
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
            $orders = array_merge($orders, $this->arraytoArray($orderList->getOrders()));
            
            if ($orderList->getNextToken()) {
                $nextToken = $orderList->getNextToken();
            } else {
                return  $orders;
            }
        }


        return $orders;
    }

    

    protected function arraytoArray($elements)
    {
        $transformed = [];
        foreach ($elements as $element) {
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
        $reponse = $this->getSdkOrders()->getOrder($this->getRestrictedToken(), Regions::EUROPE, $orderNumber);
        return json_decode($reponse->jsonSerialize(), true);
    }



    public function markOrderAsFulfill($orderId, $orderApi, $carrierCode, $carrierName, $shippingMethod, $trackingNumber):bool
    {
        $confirmShipmentRequest = new ConfirmShipmentRequest();
        $confirmShipmentRequest->setMarketplaceId($this->getMarketplaceId());
        $packageDetails = new PackageDetail();
        $packageDetails->setPackageReferenceId('1');
        $packageDetails->setCarrierCode($carrierCode);
        $packageDetails->setCarrierName($carrierName);
        $packageDetails->setShippingMethod($shippingMethod);
        $packageDetails->setTrackingNumber($trackingNumber);
        $packageDetails->setShipDate(new DateTime());

        $items = [];
        foreach ($orderApi['Lines'] as $line) {
            $confirmShipmentOrderItem = new ConfirmShipmentOrderItem();
            $confirmShipmentOrderItem->setOrderItemId($line['OrderItemId']);
            $confirmShipmentOrderItem->setQuantity($line['QuantityOrdered']);
            $items[]=$confirmShipmentOrderItem;
        }

        $packageDetails->setOrderItems($items);

        $confirmShipmentRequest->setPackageDetail($packageDetails);
        $confirmShipmentResponse = $this->getSdkOrders()->confirmShipment($this->getAccessToken(), Regions::EUROPE, $orderId, $confirmShipmentRequest);
        return true;
    }


    
    public function sendInvoice($orderId, $totalAmountIncludingTax, $totalTaxAmount, $invoiceNumber, $contentPdf):bool
    {
        $sdkFeed = $this->getSdkFeeds();
        $createFeedDocSpec = new CreateFeedDocumentSpecification();
        $createFeedDocSpec->setContentType('application/pdf');
        $response = $sdkFeed->createFeedDocument($this->getAccessToken(), Regions::EUROPE,$createFeedDocSpec);
        
        $feedDocumentId = $response->getFeedDocumentId();
        $uploadUrl = $response->getUrl();

        $httpClient = new Client();

        $reponse = $httpClient->put($uploadUrl, [
            'body' => $contentPdf,
            'headers' => [
                'Content-Type' => 'application/pdf',
            ],
        ]);

        dump($reponse->getStatusCode());




       $createFeedSpec = new CreateFeedSpecification();
       $createFeedSpec->setFeedOptions(
                [
                    'metadata:OrderId' => $orderId,
                    'metadata:InvoiceNumber' => $invoiceNumber,
                    'metadata:TotalAmount' => strval($totalAmountIncludingTax),
                    'metadata:TotalVATAmount' => strval($totalTaxAmount),
                ]
       );

       $createFeedSpec->setFeedType('UPLOAD_VAT_INVOICE');
       $createFeedSpec->setMarketplaceIds([$this->getMarketplaceId()]);
       $createFeedSpec->setInputFeedDocumentId($feedDocumentId);

        // Step 3: Submit Feed
        $feedResponse = $sdkFeed->createFeed($this->getAccessToken(), Regions::EUROPE, $createFeedSpec);

        $feedId = $feedResponse->getFeedId();


        return true;
    }

}
