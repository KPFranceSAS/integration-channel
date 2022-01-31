<?php

namespace App\Service\ChannelAdvisor;

use App\Entity\IntegrationFile;
use App\Entity\WebOrder;
use App\Helper\BusinessCentral\Connector\BusinessCentralConnector;
use App\Helper\BusinessCentral\Model\SaleOrder;
use App\Helper\BusinessCentral\Model\SaleOrderLine;
use App\Service\BusinessCentral\BusinessCentralAggregator;
use App\Service\ChannelAdvisor\ChannelWebservice;
use App\Service\ChannelAdvisor\TransformOrder;
use App\Service\Integrator\IntegratorParent;
use App\Service\MailService;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;
use stdClass;


/**
 * Services that will get through the API the order from ChannelAdvisor
 * 
 */
class IntegrateOrdersChannelAdvisor extends IntegratorParent
{

    private $channel;



    public function __construct(
        ManagerRegistry $manager,
        LoggerInterface $logger,
        MailService $mailer,
        ChannelWebservice $channel,
        BusinessCentralAggregator $businessCentralAggregator
    ) {
        $businessCentralConnector = $businessCentralAggregator->getBusinessCentralConnector(BusinessCentralConnector::KP_FRANCE);
        parent::__construct($manager, $logger, $mailer, $businessCentralConnector);
        $this->channel = $channel;
    }


    public function getChannel()
    {
        return WebOrder::CHANNEL_CHANNELADVISOR;
    }




    public function integrateAllOrders()
    {
        $counter = 0;
        $ordersApi = $this->channel->getNewOrdersByBatch(true);
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
                $ordersApi = $this->channel->getNextResults($ordersApi->{'@odata.nextLink'});
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




    protected function checkAfterPersist(WebOrder $order, stdClass $orderApi)
    {
        $this->addLogToOrder($order, 'Marked on channel advisor as exported');
        $this->channel->markOrderAsExported($orderApi->ID);
    }


    protected function getOrderId(stdClass $orderApi)
    {
        return $orderApi->SiteOrderID;
    }





    protected function checkToIntegrateToInvoice($order): bool
    {
        if ($this->isAlreadyRecordedDatabase($order->SiteOrderID)) {
            $this->channel->markOrderAsExported($order->ID);
            $this->logger->info('Marked on channel advisor as exported');
            $this->logger->info('Is Already Recorded Database');
            return false;
        }
        if ($this->alreadyIntegratedErp($order)) {
            $this->channel->markOrderAsExported($order->ID);
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
     * @param stdClass $orderApi
     * @return boolean
     */
    protected function alreadyIntegratedErp($orderApi): bool
    {
        return $this->checkIfInvoice($orderApi->SiteOrderID) || $this->checkIfOrder($orderApi->SiteOrderID)  || $this->checkIfPostedInvoice($orderApi);
    }





    /**
     * 
     * 
     * @param stdClass $orderApi
     * @return boolean
     */
    protected function checkIfPostedInvoice($orderApi): bool
    {
        $this->logger->info('Check post invoice in export file ' . $orderApi->SiteOrderID);
        $files = $this->manager->getRepository(IntegrationFile::class)->findBy(
            [
                'externalOrderId' => $orderApi->SiteOrderID,
                'documentType' => IntegrationFile::TYPE_INVOICE
            ]
        );
        return count($files) > 0;
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




    /**
     * Transform an order as serialized to array
     *
     * @param stdClass $order
     * @return SaleOrder
     */
    public function transformToAnBcOrder(stdClass $orderApi): SaleOrder
    {
        $orderBC = new SaleOrder();
        $orderBC->customerNumber = $this->matchChannelAdvisorOrderToCustomer($orderApi->ProfileID, $orderApi->SiteID);

        $orderBC->billToName = $orderApi->BillingFirstName . ' ' . $orderApi->BillingLastName;

        $orderBC->sellingPostalAddress->street = substr($orderApi->BillingAddressLine1, 0, 100);
        if (strlen($orderApi->BillingAddressLine2) > 0) {
            $orderBC->sellingPostalAddress->street .= "\r\n" . substr($orderApi->BillingAddressLine2, 0, 100);
        }
        $orderBC->sellingPostalAddress->city = $orderApi->BillingCity;
        $orderBC->sellingPostalAddress->postalCode = $orderApi->BillingPostalCode;
        $orderBC->sellingPostalAddress->countryLetterCode = $orderApi->BillingCountry;
        if (strlen($orderApi->BillingStateOrProvinceName) > 0 && $orderApi->BillingStateOrProvinceName != "--") {
            $orderBC->sellingPostalAddress->state = $orderApi->BillingStateOrProvinceName;
        }


        $orderBC->shipToName = $orderApi->ShippingFirstName . ' ' . $orderApi->ShippingLastName;
        $orderBC->shippingPostalAddress->street = substr($orderApi->ShippingAddressLine1, 0, 100);
        if (strlen($orderApi->ShippingAddressLine2) > 0) {
            $orderBC->shippingPostalAddress->street .= "\r\n" . substr($orderApi->ShippingAddressLine2, 0, 100);
        }
        $orderBC->shippingPostalAddress->city = $orderApi->ShippingCity;
        $orderBC->shippingPostalAddress->postalCode = $orderApi->ShippingPostalCode;
        $orderBC->shippingPostalAddress->countryLetterCode = $orderApi->ShippingCountry;
        if (strlen($orderApi->ShippingStateOrProvinceName) > 0 && $orderApi->ShippingStateOrProvinceName != "--") {
            $orderBC->shippingPostalAddress->state = $orderApi->ShippingStateOrProvinceName;
        }

        $orderBC->email = $orderApi->BuyerEmailAddress;
        $orderBC->phoneNumber = $orderApi->BillingDaytimePhone;
        $orderBC->externalDocumentNumber = $orderApi->SiteOrderID;

        if ($orderApi->Currency != 'EUR') {
            $orderBC->currencyCode =  $orderApi->Currency;
        }

        $orderBC->pricesIncludeTax = true; // enables BC to do VAT autocalculation
        $orderBC->salesLines = $this->getSalesOrderLines($orderApi->Items, $orderApi->AdditionalCostOrDiscount);

        return $orderBC;
    }


    /**
     * Transform lines from Api to BC model
     *
     * @param array $saleLineApis
     * @param float $additionalCostOrDiscount
     * @return SaleOrderLine[]
     */
    private function getSalesOrderLines(array $saleLineApis, $additionalCostOrDiscount): array
    {
        $saleOrderLines = [];
        $shippingPrice = 0;
        foreach ($saleLineApis as $line) {

            $saleLine = new SaleOrderLine();
            $saleLine->lineType = SaleOrderLine::TYPE_ITEM;
            $saleLine->itemId = $this->getProductCorrelationSku($line->Sku);
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
            $account = $this->businessCentralConnector->getAccountForExpedition();
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






    /**
     * Get Customer client according to profile 
     *
     * @param string $profileId
     * @param string $siteId
     * @return string
     */
    private function matchChannelAdvisorOrderToCustomer(string $profileId, string $siteId): string
    {
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
            throw new Exception("Profile Id $profileId, SiteId $siteId is not mapped to a customer");
        }
    }
}
