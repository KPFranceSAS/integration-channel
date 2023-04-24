<?php

namespace App\Channels\Cdiscount;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\BusinessCentral\Model\SaleOrder;
use App\BusinessCentral\Model\SaleOrderLine;
use App\Channels\Cdiscount\CdiscountApi;
use App\Entity\IntegrationChannel;
use App\Entity\WebOrder;
use App\Service\Aggregator\IntegratorParent;
use Exception;
use stdClass;

/**
 * Services that will get through the API the order from Cdiscount
 *
 */
class CdiscountIntegrateOrder extends IntegratorParent
{

    public const CDISCOUNT_FR = '000809';


    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_CDISCOUNT;
    }



    protected function getOrderId($orderApi)
    {
        return $orderApi->SiteOrderID;
    }



    protected function getCdiscountApi(): CdiscountApi
    {
        return $this->getApi();
    }


    






    public function transformToAnBcOrder($orderApi): SaleOrder
    {
        $orderBC = new SaleOrder();
        $orderBC->customerNumber = $this->getCustomerBC($orderApi);

        $orderBC->billToName = $orderApi->BillingFirstName . ' ' . $orderApi->BillingLastName;

        $orderBC->sellingPostalAddress->street = substr($orderApi->BillingAddressLine1, 0, 100);
        if ($orderApi->BillingAddressLine2 && strlen($orderApi->BillingAddressLine2) > 0) {
            $orderBC->sellingPostalAddress->street .= "\r\n" . substr($orderApi->BillingAddressLine2, 0, 100);
        }
        $orderBC->sellingPostalAddress->city = substr($orderApi->BillingCity, 0, 100);
        $orderBC->sellingPostalAddress->postalCode = $orderApi->BillingPostalCode;
        $orderBC->sellingPostalAddress->countryLetterCode = $orderApi->BillingCountry;
        if ($orderApi->BillingStateOrProvinceName && strlen($orderApi->BillingStateOrProvinceName) > 0 && $orderApi->BillingStateOrProvinceName != "--") {
            $orderBC->sellingPostalAddress->state = substr($orderApi->BillingStateOrProvinceName, 0, 30);
        }

        $orderBC->locationCode = WebOrder::DEPOT_LAROCA;


        $orderBC->shipToName = $orderApi->ShippingFirstName . ' ' . $orderApi->ShippingLastName;
        if ($orderApi->ShippingAddressLine1) {
            $orderBC->shippingPostalAddress->street = substr($orderApi->ShippingAddressLine1, 0, 100);
        } else {
            $orderBC->shippingPostalAddress->street='';
        }
        if ($orderApi->ShippingAddressLine2 && strlen($orderApi->ShippingAddressLine2) > 0) {
            $orderBC->shippingPostalAddress->street .= "\r\n" . substr($orderApi->ShippingAddressLine2, 0, 100);
        }
        $orderBC->shippingPostalAddress->city = substr($orderApi->ShippingCity, 0, 100);
        $orderBC->shippingPostalAddress->postalCode = $orderApi->ShippingPostalCode;
        $orderBC->shippingPostalAddress->countryLetterCode = $orderApi->ShippingCountry;
        if ($orderApi->ShippingStateOrProvinceName && strlen($orderApi->ShippingStateOrProvinceName) > 0 && $orderApi->ShippingStateOrProvinceName != "--") {
            $orderBC->shippingPostalAddress->state = substr($orderApi->BillingStateOrProvinceName, 0, 30);
        }

        $orderBC->email = $orderApi->BuyerEmailAddress;
        $orderBC->phoneNumber = $orderApi->BillingDaytimePhone;
        $orderBC->externalDocumentNumber = $orderApi->SiteOrderID;

        if ($orderApi->Currency != 'EUR') {
            $orderBC->currencyCode =  $orderApi->Currency;
        }

        $orderBC->shippingAgent = 'FBA';
        $orderBC->shippingAgentService = '1';

        $orderBC->pricesIncludeTax = true; // enables BC to do VAT autocalculation
        $orderBC->salesLines = $this->getSalesOrderLines($orderApi);
        return $orderBC;
    }


    private function getSalesOrderLines($orderApi): array
    {
        $saleOrderLines = [];
        $shippingPrice = 0;
        $company =  $this->getCompanyIntegration($orderApi);

        foreach ($orderApi->Items as $line) {
            $saleLine = new SaleOrderLine();
            $saleLine->lineType = SaleOrderLine::TYPE_ITEM;
            $saleLine->itemId = $this->getProductCorrelationSku($line->Sku, $company);
            // calculate price and shipping fees
            $shippingPrice += $line->ShippingPrice;
            $promotionAmount = 0;
            if (count($line->Promotions) > 0) {
                foreach ($line->Promotions as $promotion) {
                    if ($promotion->Amount != 0) {
                        $promotionAmount += $promotion->Amount;
                    }
                    if ($promotion->ShippingAmount != 0) {
                        $shippingPrice += $promotion->ShippingAmount;
                    }
                }
            }

            $saleLine->unitPrice = $line->UnitPrice;
            $saleLine->quantity = $line->Quantity;
            $saleLine->discountAmount = abs($promotionAmount);
            $saleOrderLines[] = $saleLine;
        }

        // ajout livraison
        if ($shippingPrice > 0) {
            $account = $this->getBusinessCentralConnector($company)->getAccountForExpedition();
            $saleLineDelivery = new SaleOrderLine();
            $saleLineDelivery->lineType = SaleOrderLine::TYPE_GLACCOUNT;
            $saleLineDelivery->quantity = 1;
            $saleLineDelivery->accountId = $account['id'];
            $saleLineDelivery->unitPrice = $shippingPrice;
            $saleLineDelivery->description = 'SHIPPING FEES';
            $saleOrderLines[] = $saleLineDelivery;
        }
        return $saleOrderLines;
    }


    public function getCustomerBC($orderApi)
    {
        return self::CDISCOUNT_FR;
    }



    public function getCompanyIntegration($orderApi)
    {
       
        return BusinessCentralConnector::KP_FRANCE;
       
    }




}
