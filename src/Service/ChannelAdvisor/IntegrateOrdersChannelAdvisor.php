<?php

namespace App\Service\ChannelAdvisor;

use App\Entity\WebOrder;
use App\Helper\BusinessCentral\Connector\BusinessCentralConnector;
use App\Helper\BusinessCentral\Model\SaleOrder;
use App\Helper\BusinessCentral\Model\SaleOrderLine;
use App\Helper\Integrator\IntegratorParent;
use Exception;
use stdClass;


/**
 * Services that will get through the API the order from ChannelAdvisor
 * 
 */
class IntegrateOrdersChannelAdvisor extends IntegratorParent
{

    public function getChannel()
    {
        return WebOrder::CHANNEL_CHANNELADVISOR;
    }


    public function integrateAllOrders()
    {
        $counter = 0;
        $ordersApi = $this->getApi()->getNewOrdersByBatch(true);
        $this->logLine('Integration first batch');
        foreach ($ordersApi->value as $orderApi) {
            if ($this->integrateOrder($orderApi)) {
                $counter++;
                $this->logger->info("Orders integrated : $counter ");
            }
        }

        while (true) {
            if (property_exists($ordersApi, '@odata.nextLink')) {

                $this->logLine('Integration next batch');
                $ordersApi = $this->getApi()->getNextResults($ordersApi->{'@odata.nextLink'});
                foreach ($ordersApi->value as $orderApi) {
                    if ($this->integrateOrder($orderApi)) {
                        $counter++;
                        $this->logger->info("Orders integrated : $counter ");
                    }
                }
            } else {
                return;
            }
        }
    }


    protected function checkAfterPersist(WebOrder $order, $orderApi)
    {
        $this->addLogToOrder($order, 'Marked on channel advisor as exported');
        $this->getApi()->markOrderAsExported($orderApi->ID);
    }


    protected function getOrderId($orderApi)
    {
        return $orderApi->SiteOrderID;
    }


    protected function checkToIntegrateToInvoice($order): bool
    {
        $company = $this->getCompanyIntegration($order);
        $customer = $this->getCustomerBC($order);
        if ($this->isAlreadyRecordedDatabase($order->SiteOrderID)) {
            $this->getApi()->markOrderAsExported($order->ID);
            $this->logger->info('Marked on channel advisor as exported');
            $this->logger->info('Is Already Recorded Database');
            return false;
        }
        if ($this->alreadyIntegratedErp($order->SiteOrderID, $company, $customer)) {
            $this->getApi()->markOrderAsExported($order->ID);
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
        if (($orderApi->DistributionCenterTypeRollup == 'ExternallyManaged' && $orderApi->ShippingStatus == 'Shipped')) {
            $this->logger->info('Status OK');
            return true;
        } else {
            $this->logger->info("X__Status Bad " . $orderApi->DistributionCenterTypeRollup . " " . $orderApi->ShippingStatus);
            return false;
        }
    }




    public function transformToAnBcOrder($orderApi): SaleOrder
    {
        $orderBC = new SaleOrder();
        $orderBC->customerNumber = $this->matchChannelAdvisorOrderToCustomer($orderApi);

        $orderBC->billToName = $orderApi->BillingFirstName . ' ' . $orderApi->BillingLastName;

        $orderBC->sellingPostalAddress->street = substr($orderApi->BillingAddressLine1, 0, 100);
        if (strlen($orderApi->BillingAddressLine2) > 0) {
            $orderBC->sellingPostalAddress->street .= "\r\n" . substr($orderApi->BillingAddressLine2, 0, 100);
        }
        $orderBC->sellingPostalAddress->city = substr($orderApi->BillingCity, 0, 100);
        $orderBC->sellingPostalAddress->postalCode = $orderApi->BillingPostalCode;
        $orderBC->sellingPostalAddress->countryLetterCode = $orderApi->BillingCountry;
        if (strlen($orderApi->BillingStateOrProvinceName) > 0 && $orderApi->BillingStateOrProvinceName != "--") {
            $orderBC->sellingPostalAddress->state = substr($orderApi->BillingStateOrProvinceName, 0, 30);
        }

        $orderBC->locationCode = WebOrder::DEPOT_FBA_AMAZON;


        $orderBC->shipToName = $orderApi->ShippingFirstName . ' ' . $orderApi->ShippingLastName;
        $orderBC->shippingPostalAddress->street = substr($orderApi->ShippingAddressLine1, 0, 100);
        if (strlen($orderApi->ShippingAddressLine2) > 0) {
            $orderBC->shippingPostalAddress->street .= "\r\n" . substr($orderApi->ShippingAddressLine2, 0, 100);
        }
        $orderBC->shippingPostalAddress->city = substr($orderApi->ShippingCity, 0, 100);
        $orderBC->shippingPostalAddress->postalCode = $orderApi->ShippingPostalCode;
        $orderBC->shippingPostalAddress->countryLetterCode = $orderApi->ShippingCountry;
        if (strlen($orderApi->ShippingStateOrProvinceName) > 0 && $orderApi->ShippingStateOrProvinceName != "--") {
            $orderBC->shippingPostalAddress->state = substr($orderApi->BillingStateOrProvinceName, 0, 30);
        }

        $orderBC->email = $orderApi->BuyerEmailAddress;
        $orderBC->phoneNumber = $orderApi->BillingDaytimePhone;
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
        $mapCustomer = [
            "12010024" =>   "000223", // Customer Amazon UK
            "12010025" =>   "000163", // Customer Amazon IT
            "12010023" =>   "000193", // Customer Amazon DE
            "12009934" =>   "000222", // Customer Amazon FR
            "12010026" =>   "000230", // Customer Amazon ES
        ];
        if (array_key_exists($profileId, $mapCustomer)) {
            return $mapCustomer[$profileId];
        } else {
            throw new Exception("Profile Id $profileId is not mapped to a customer");
        }
    }
}
