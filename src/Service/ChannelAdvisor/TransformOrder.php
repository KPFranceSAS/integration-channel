<?php

namespace App\Service\ChannelAdvisor;

use App\Entity\ProductCorrelation;
use App\Helper\BusinessCentral\Model\SaleOrder;
use App\Helper\BusinessCentral\Model\SaleOrderLine;
use App\Service\BusinessCentral\BusinessCentralConnector;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;
use stdClass;


/**
 * Services that will get through the API the order from ChannelAdvisor
 * 
 */
class TransformOrder
{

    private $logger;

    private $businessCentralConnector;

    private $manager;

    public function __construct(ManagerRegistry $manager, LoggerInterface $logger, BusinessCentralConnector $businessCentralConnector)
    {
        $this->logger = $logger;
        $this->manager = $manager->getManager();
        $this->businessCentralConnector = $businessCentralConnector;
    }

    /**
     * Transform an order as serialized to array
     *
     * @param stdClass $order
     * @return SaleOrder
     */
    public function transformToAnFBAOrder(stdClass $orderApi): SaleOrder
    {
        $orderBC = new SaleOrder();
        $orderBC->customerNumber = $this->matchChannelAdvisorOrderToCustomer($orderApi->ProfileID, $orderApi->SiteID);

        $orderBC->billToName = $orderApi->BillingFirstName . ' ' . $orderApi->BillingLastName;

        $orderBC->sellingPostalAddress->street = $orderApi->BillingAddressLine1;
        if (strlen($orderApi->BillingAddressLine2) > 0) {
            $orderBC->sellingPostalAddress->street .= "\r\n" . $orderApi->BillingAddressLine2;
        }
        $orderBC->sellingPostalAddress->city = $orderApi->BillingCity;
        $orderBC->sellingPostalAddress->postalCode = $orderApi->BillingPostalCode;
        $orderBC->sellingPostalAddress->countryLetterCode = $orderApi->BillingCountry;
        if (strlen($orderApi->BillingStateOrProvinceName) > 0 && $orderApi->BillingStateOrProvinceName != "--") {
            $orderBC->sellingPostalAddress->state = $orderApi->BillingStateOrProvinceName;
        }


        $orderBC->shipToName = $orderApi->ShippingFirstName . ' ' . $orderApi->ShippingLastName;
        $orderBC->shippingPostalAddress->street = $orderApi->ShippingAddressLine1;
        if (strlen($orderApi->ShippingAddressLine2) > 0) {
            $orderBC->shippingPostalAddress->street .= "\r\n" . $orderApi->ShippingAddressLine2;
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

            $saleLine->unitPrice = $line->UnitPrice * $line->Quantity;
            $saleLine->quantity = $line->Quantity;
            $saleLine->discountAmount = abs($promotionAmount);
            $saleOrderLines[] = $saleLine;
        }

        // ajout livraison 
        if ($shippingPrice > 0) {
            $saleLineDelivery = new SaleOrderLine();
            $saleLineDelivery->lineType = SaleOrderLine::TYPE_GLACCOUNT;
            $saleLineDelivery->quantity = 1;
            $saleLineDelivery->unitPrice = $shippingPrice;
            $saleLineDelivery->description = 'SHIPPING FEES';
            $saleLineDelivery->lineDetails = [
                "number" => "758000"
            ];
            $saleOrderLines[] = $saleLineDelivery;
        }
        return $saleOrderLines;
    }



    /**
     * Undocumented function
     *
     * @param string $sku
     * @return string
     */
    private function getProductCorrelationSku(string $sku): string
    {
        $skuSanitized = strtoupper($sku);
        $productCorrelation = $this->manager->getRepository(ProductCorrelation::class)->findOneBy(['skuUsed' => $skuSanitized]);
        $skuFinal = $productCorrelation ? $productCorrelation->getSkuErp() : $skuSanitized;

        $product = $this->businessCentralConnector->getItemByNumber($skuFinal);
        if (!$product) {
            throw new Exception("Product with Sku $skuFinal cannot be found in business central. Check Product correlation ");
        } else {
            return  $product['id'];
        }
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
