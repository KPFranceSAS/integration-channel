<?php

namespace App\Helper\Integrator;

use App\Entity\WebOrder;
use App\BusinessCentral\Model\SaleOrder;
use App\BusinessCentral\Model\SaleOrderLine;
use App\Helper\Integrator\IntegratorParent;
use App\Helper\Utils\DatetimeUtils;


abstract class ShopifyIntegrateOrder extends IntegratorParent
{

    abstract protected function getSuffix();


    protected function getOrderId($orderApi)
    {
        return $this->getSuffix() . $orderApi['order_number'];
    }



    public function transformToAnBcOrder($orderApi): SaleOrder
    {
        $orderBC = new SaleOrder();
        $orderBC->customerNumber = $this->getCustomerBC($orderApi);

        $dateCreated = DatetimeUtils::transformFromIso8601($orderApi['processed_at']);
        $dateCreated->add(new \DateInterval('P3D'));
        $orderBC->requestedDeliveryDate = $dateCreated->format('Y-m-d');
        $orderBC->locationCode = WebOrder::DEPOT_LAROCA;
        $orderBC->billToName = $orderApi['billing_address']['name'];
        $orderBC->shipToName = $orderApi['shipping_address']['name'];


        $this->transformAddress($orderBC, $orderApi['shipping_address'], 'shipping');
        $this->transformAddress($orderBC, $orderApi['billing_address'], 'selling');

        if ($orderApi['currency'] != 'EUR') {
            $orderBC->currencyCode =  $orderApi['currency'];
        }

        if (strlen($orderApi['shipping_address']["phone"]) > 0) {
            $orderBC->phoneNumber = $orderApi['shipping_address']["phone"];
        } elseif (strlen($orderApi['billing_address']["phone"]) > 0) {
            $orderBC->phoneNumber = $orderApi['billing_address']["phone"];
        }

        $orderBC->externalDocumentNumber = $this->getOrderId($orderApi);
        $orderBC->email = $orderApi['email'];

        $orderBC->pricesIncludeTax = true; // enables BC to do VAT autocalculation
        $orderBC->salesLines = $this->getSalesOrderLines($orderApi);



        $livraisonFees = floatval($orderApi['total_shipping_price_set']['shop_money']['amount']);
        // ajout livraison 
        $company = $this->getCompanyIntegration($orderApi);


        foreach ($orderApi['shipping_lines'] as $line) {
            if (floatval($line['price']) > 0) {
                $account = $this->getBusinessCentralConnector($company)->getAccountForExpedition();
                $saleLineDelivery = new SaleOrderLine();
                $saleLineDelivery->lineType = SaleOrderLine::TYPE_GLACCOUNT;
                $saleLineDelivery->quantity = 1;
                $saleLineDelivery->accountId = $account['id'];
                $saleLineDelivery->unitPrice = floatval($line['price']);
                $saleLineDelivery->description = substr('GASTOS DE ENVIO ' . strtoupper($line['code']), 0, 100);
                $orderBC->salesLines[] = $saleLineDelivery;
            }
        }


        $discounts = $this->getAllDiscountLines($orderApi);

        if (count($discounts) > 0) {
            $account = $this->getBusinessCentralConnector($company)->getAccountByNumber('7000005');
            foreach ($discounts as $discount) {
                $saleLineDelivery = new SaleOrderLine();
                $saleLineDelivery->lineType = SaleOrderLine::TYPE_GLACCOUNT;
                $saleLineDelivery->quantity = 1;
                $saleLineDelivery->accountId = $account['id'];
                $saleLineDelivery->unitPrice = -$discount['value'];
                $saleLineDelivery->description = substr(strtoupper($discount['description']), 0, 100);
                $orderBC->salesLines[] = $saleLineDelivery;
            }
        }

        return $orderBC;
    }



    private function getDescriptionDiscount($discountApplication)
    {
        if (array_key_exists('code', $discountApplication)) {
            return 'CODE ' . $discountApplication['code'];
        } elseif (array_key_exists('title', $discountApplication)) {
            return $discountApplication['title'];
        } elseif (array_key_exists('description', $discountApplication)) {
            return $discountApplication['description'];
        } else {
            return '';
        }
    }


    private function getValueDiscount($discountApplication)
    {
        if ($discountApplication['value_type'] == 'percentage') {
            return $discountApplication['value'] . '%';
        } else {
            return $discountApplication['value'] . 'EUR';
        }
    }




    private function getAllDiscountLines($orderApi)
    {
        $discounts = [];
        foreach ($orderApi['line_items'] as $line) {

            foreach ($line["discount_allocations"] as $discountLine) {
                $discountApplication = $orderApi["discount_applications"][$discountLine["discount_application_index"]];
                $discounts[] = [
                    'value' => floatval($discountLine['amount']),
                    'description' => 'DISCUENTO ' .  $line["sku"] . " / " . $this->getValueDiscount($discountApplication) . " / " . $this->getDescriptionDiscount($discountApplication)
                ];
            }
        }


        foreach ($orderApi['shipping_lines'] as $line) {

            foreach ($line["discount_allocations"] as $discountLine) {
                $discountApplication = $orderApi["discount_applications"][$discountLine["discount_application_index"]];
                $discounts[] = [
                    'value' => floatval($discountLine['amount']),
                    'description' => "DISCUENTO GASTOS DE ENVIO / " . $this->getValueDiscount($discountApplication) . " / " . $this->getDescriptionDiscount($discountApplication)
                ];
            }
        }
        return $discounts;
    }



    private function transformAddress(SaleOrder $saleOrder, array $addressShopifyType, string $addressBusinessType)
    {
        $adress =  trim($addressShopifyType['address1']);
        if (strlen($addressShopifyType['address2']) > 0) {
            $adress .= ', ' . trim($addressShopifyType['address2']);
        }
        $adress = $this->simplifyAddress($adress);
        if (strlen($adress) < 100) {
            $saleOrder->{$addressBusinessType . "PostalAddress"}->street = $adress;
        } else {
            $saleOrder->{$addressBusinessType . "PostalAddress"}->street = substr($adress, 0, 100) . "\r\n" . substr($adress, 99);
        }
        $saleOrder->{$addressBusinessType . "PostalAddress"}->city = substr($addressShopifyType['city'], 0, 100);
        $saleOrder->{$addressBusinessType . "PostalAddress"}->postalCode = $addressShopifyType['zip'];
        $saleOrder->{$addressBusinessType . "PostalAddress"}->countryLetterCode = $addressShopifyType['country_code'];
        if (strlen($addressShopifyType['province']) > 0) {
            $saleOrder->{$addressBusinessType . "PostalAddress"}->state = substr($addressShopifyType['province'], 0, 30);;
        }
    }


    private function getSalesOrderLines($orderApi): array
    {
        $saleOrderLines = [];
        $company = $this->getCompanyIntegration($orderApi);

        foreach ($orderApi['line_items'] as $line) {

            $saleLine = new SaleOrderLine();
            $saleLine->lineType = SaleOrderLine::TYPE_ITEM;
            $sku = $line['sku'];
            $saleLine->itemId = $this->getProductCorrelationSku($sku, $company);
            $saleLine->unitPrice = floatval($line["price"]);
            $saleLine->quantity = $line["quantity"];
            $saleOrderLines[] = $saleLine;
        }


        return $saleOrderLines;
    }
}
