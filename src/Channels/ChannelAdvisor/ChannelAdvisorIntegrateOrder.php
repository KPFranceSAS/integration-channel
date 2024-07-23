<?php

namespace App\Channels\ChannelAdvisor;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\BusinessCentral\Model\SaleOrder;
use App\BusinessCentral\Model\SaleOrderLine;
use App\Channels\ChannelAdvisor\ChannelAdvisorApi;
use App\Entity\IntegrationChannel;
use App\Entity\WebOrder;
use App\Service\Aggregator\IntegratorParent;
use Exception;
use stdClass;

/**
 * Services that will get through the API the order from ChannelAdvisor
 *
 */
class ChannelAdvisorIntegrateOrder extends IntegratorParent
{


    final public const AMZ_KP_FR = '000222';
    final public const AMZ_KP_ES = '000230';
    final public const AMZ_KP_IT = '000163';
    final public const AMZ_KP_DE = '000193';
    final public const AMZ_KP_UK = '000223';
    final public const AMZ_GI_ES = '003315';
    final public const CDISC_KP_FR = '000809';

 


    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_CHANNELADVISOR;
    }



    protected function checkAfterPersist(WebOrder $order, $orderApi)
    {
        $this->addLogToOrder($order, 'Marked on channel advisor as exported');
        $this->getChannelApi()->markOrderAsExported($orderApi->ID);
    }


    protected function getOrderId($orderApi)
    {
        return $orderApi->SiteOrderID;
    }



    protected function getChannelApi(): ChannelAdvisorApi
    {
        return $this->getApi();
    }


    protected function checkToIntegrateToInvoice($order): bool
    {
        $company = $this->getCompanyIntegration($order);
        $customer = $this->getCustomerBC($order);
        if ($this->isAlreadyRecordedDatabase($order->SiteOrderID)) {
            $this->getChannelApi()->markOrderAsExported($order->ID);
            $this->logger->info('Marked on channel advisor as exported');
            $this->logger->info('Is Already Recorded Database');
            return false;
        }
        if ($this->alreadyIntegratedErp($order->SiteOrderID, $company, $customer)) {
            $this->getChannelApi()->markOrderAsExported($order->ID);
            $this->logger->info('Marked on channel advisor as exported');
            $this->logger->info('Is Already Recorded on ERP');
            return false;
        }
        if (!$this->checkStatusToInvoice($order)) {
            $this->logger->info('Status is not good for integration');
            return false;
        }
        return true;
    }




    /**
     * Check status of order
     *
     * @param stdClass $order
     * @return boolean
     */
    protected function checkStatusToInvoice($orderApi): bool
    {
        if (
            $orderApi->DistributionCenterTypeRollup == 'ExternallyManaged'
            && $orderApi->ShippingStatus == 'Shipped'
        ) {
            $this->logger->info('Status OK');
            return true;
        } elseif(
            $orderApi->DistributionCenterTypeRollup == 'SellerManaged'
            && $orderApi->ShippingStatus == 'Unshipped'
        ) {
            $this->logger->info('Status OK');
            return true;
        } else {
            $this->logger->info("X__Bad " . $orderApi->DistributionCenterTypeRollup . " " . $orderApi->ShippingStatus);
            return false;
        }
    }




    public function transformToAnBcOrder($orderApi): SaleOrder
    {
        $orderBC = new SaleOrder();
        $orderBC->customerNumber = $this->matchChannelAdvisorOrderToCustomer($orderApi);

        $orderBC->billToName = $orderApi->BillingFirstName . ' ' . $orderApi->BillingLastName;

        $orderBC->sellingPostalAddress->street = substr((string) $orderApi->BillingAddressLine1, 0, 100);
        if ($orderApi->BillingAddressLine2 && strlen((string) $orderApi->BillingAddressLine2) > 0) {
            $orderBC->sellingPostalAddress->street .= "\r\n" . substr((string) $orderApi->BillingAddressLine2, 0, 100);
        }
        $orderBC->sellingPostalAddress->city = substr((string) $orderApi->BillingCity, 0, 100);
        $orderBC->sellingPostalAddress->postalCode = $orderApi->BillingPostalCode;
        $orderBC->sellingPostalAddress->countryLetterCode = $orderApi->BillingCountry;
        if ($orderApi->BillingStateOrProvinceName && strlen((string) $orderApi->BillingStateOrProvinceName) > 0 && $orderApi->BillingStateOrProvinceName != "--") {
            $orderBC->sellingPostalAddress->state = substr((string) $orderApi->BillingStateOrProvinceName, 0, 30);
        }

        $orderBC->shipToName = $orderApi->ShippingFirstName . ' ' . $orderApi->ShippingLastName;
        if ($orderApi->ShippingAddressLine1) {
            $orderBC->shippingPostalAddress->street = substr((string) $orderApi->ShippingAddressLine1, 0, 100);
        } else {
            $orderBC->shippingPostalAddress->street='';
        }
        if ($orderApi->ShippingAddressLine2 && strlen((string) $orderApi->ShippingAddressLine2) > 0) {
            $orderBC->shippingPostalAddress->street .= "\r\n" . substr((string) $orderApi->ShippingAddressLine2, 0, 100);
        }
        $orderBC->shippingPostalAddress->city = substr((string) $orderApi->ShippingCity, 0, 100);
        $orderBC->shippingPostalAddress->postalCode = $orderApi->ShippingPostalCode;
        $orderBC->shippingPostalAddress->countryLetterCode = $orderApi->ShippingCountry;
        if ($orderApi->ShippingStateOrProvinceName && strlen((string) $orderApi->ShippingStateOrProvinceName) > 0 && $orderApi->ShippingStateOrProvinceName != "--") {
            $orderBC->shippingPostalAddress->state = substr((string) $orderApi->BillingStateOrProvinceName, 0, 30);
        }

        $orderBC->email = $orderApi->BuyerEmailAddress!='--'? $orderApi->BuyerEmailAddress : null;

        if($orderApi->ShippingEveningPhone && strlen($orderApi->ShippingEveningPhone)>0) {
            $orderBC->phoneNumber = $orderApi->BillingDaytimePhone;
        } elseif($orderApi->ShippingDaytimePhone && strlen($orderApi->ShippingDaytimePhone)>0) {
            $orderBC->phoneNumber = $orderApi->ShippingDaytimePhone;
        } elseif($orderApi->BillingEveningPhone && strlen($orderApi->BillingEveningPhone)>0) {
            $orderBC->phoneNumber = $orderApi->BillingEveningPhone;
        } else {
            $orderBC->phoneNumber = $orderApi->BillingDaytimePhone;
        }

      
        $orderBC->externalDocumentNumber = $orderApi->SiteOrderID;

        if ($orderApi->Currency != 'EUR') {
            $orderBC->currencyCode =  $orderApi->Currency;
        }

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

            $sku = $line->ReferenceSku ?  $line->ReferenceSku : $line->Sku;


            $saleLine->itemId = $this->getProductCorrelationSku($sku, $company);
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
        return $this->matchChannelAdvisorOrderToCustomer($orderApi);
    }



    public function getCompanyIntegration($orderApi)
    {
        $profileId = $orderApi->ProfileID;
        $mapCustomer = [
            "12010024" =>   BusinessCentralConnector::KP_FRANCE,
            "12010025" =>   BusinessCentralConnector::KP_FRANCE,
            "12010023" =>   BusinessCentralConnector::KP_FRANCE,
            "12009934" =>   BusinessCentralConnector::KP_FRANCE,
            "12010026" =>   BusinessCentralConnector::KP_FRANCE,
            "12044694" =>   BusinessCentralConnector::GADGET_IBERIA,
            "12047712" =>   BusinessCentralConnector::KP_FRANCE, // test
        ];
        if (array_key_exists($profileId, $mapCustomer)) {
            return $mapCustomer[$profileId];
        } else {
            throw new Exception("Profile Id $profileId to a company");
        }
    }



    private function matchChannelAdvisorOrderToCustomer($orderApi): string
    {
        $profileId = $orderApi->ProfileID;
        $siteID = $orderApi->SiteID;

        $keyMatch = $profileId.'_'.$siteID;

        $mapCustomer = [
            "12010024_641" =>  self::AMZ_KP_UK, // Customer Amazon UK KP France
            "12010025_645" =>  self::AMZ_KP_IT , // Customer Amazon IT KP France
            "12010023_642" =>  self::AMZ_KP_DE, // Customer Amazon DE KP France
            "12009934_643" =>  self::AMZ_KP_FR, // Customer Amazon FR KP France
            "12010026_683" =>  self::AMZ_KP_ES, // Customer Amazon ES KP France
            "12009934_967" =>  self::CDISC_KP_FR, // Customer Cdiscount FR KP France
            "12044694_683" =>  self::AMZ_GI_ES, // Customer Amazon ES GI
            
            '12047712_967' =>  self::AMZ_KP_FR, // Customer Test Cdiscount FR KP France
        ];

        if (array_key_exists($keyMatch, $mapCustomer)) {
            
            return $mapCustomer[$keyMatch];
        } else {
            throw new Exception("Profile Id $profileId and Siteid $siteID is not mapped to a customer");
        }
    }

}
