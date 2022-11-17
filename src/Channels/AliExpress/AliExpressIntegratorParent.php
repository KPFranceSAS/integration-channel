<?php

namespace App\Channels\AliExpress;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\BusinessCentral\Model\PostalAddress;
use App\BusinessCentral\Model\SaleOrder;
use App\BusinessCentral\Model\SaleOrderLine;
use App\Channels\AliExpress\AliExpressApiParent;
use App\Entity\WebOrder;
use App\Helper\Utils\DatetimeUtils;
use App\Service\Aggregator\IntegratorParent;
use App\Service\Aggregator\StockParent;
use DateInterval;
use DateTime;
use Exception;
use function Symfony\Component\String\u;

abstract class AliExpressIntegratorParent extends IntegratorParent
{
    /**
     * process all invocies directory
     *
     * @return void
     */
    public function integrateAllOrders()
    {
        $counter = 0;
        $ordersApi = $this->getApi()->getAllOrdersToSend();

        foreach ($ordersApi as $orderApi) {
            try {
                $orderFull = $this->getAliExpressApi()->getOrder($orderApi->order_id);

                $datePurchase = DatetimeUtils::createDateTimeFromDateWithDelay($orderFull->gmt_pay_success);
                $now = new DateTime();
                $interval = $now->diff($datePurchase, true);
                $totalHours = $interval->format('%a') * 24 + $interval->format('%h');
                $this->logger->info("Order has been purchased $totalHours hour ago");
                if ($totalHours > 1) {
                    if ($this->integrateOrder($orderFull)) {
                        $counter++;
                        $this->logger->info("Orders integrated : $counter ");
                    }
                } else {
                    $this->logger->alert("Order has been purchased before $totalHours hour ago");
                }
            } catch (Exception $exception) {
                $this->addError('Problem retrieved Aliexpress #' . $orderApi->order_id . ' > ' . $exception->getMessage());
            }
        }
    }




    protected function getAliExpressApi(): AliExpressApiParent
    {
        return $this->getApi();
    }



    protected function getOrderId($orderApi)
    {
        return $orderApi->id;
    }



    abstract protected function getClientNumber();


    public function getCustomerBC($orderApi)
    {
        return $this->getClientNumber();
    }


    public function getCompanyIntegration($orderApi)
    {
        return BusinessCentralConnector::GADGET_IBERIA;
    }



    public function transformToAnBcOrder($orderApi): SaleOrder
    {
        $orderBC = new SaleOrder();
        $orderBC->customerNumber = $this->getClientNumber();
        $datePayment = DateTime::createFromFormat('Y-m-d', substr($orderApi->gmt_pay_success, 0, 10));
        $datePayment->add(new DateInterval('P3D'));
        $orderBC->requestedDeliveryDate = $datePayment->format('Y-m-d');
        $orderBC->locationCode = WebOrder::DEPOT_LAROCA;
        $orderBC->billToName = $orderApi->receipt_address->contact_person;
        $orderBC->shipToName = $orderApi->receipt_address->contact_person;

        $valuesAddress = ['selling', 'shipping'];

        foreach ($valuesAddress as $val) {
            $adress =  $orderApi->receipt_address->detail_address;
            if (property_exists($orderApi->receipt_address, 'address2') && strlen($orderApi->receipt_address->address2) > 0) {
                $adress .= ', ' . $orderApi->receipt_address->address2;
            }

            $adress = $this->simplifyAddress($adress);

            if (strlen($adress) < 100) {
                $orderBC->{$val . "PostalAddress"}->street = $adress;
            } else {
                $orderBC->{$val . "PostalAddress"}->street = substr($adress, 0, 100) . "\r\n" . substr($adress, 99);
            }
            $orderBC->{$val . "PostalAddress"}->city = substr($orderApi->receipt_address->city, 0, 100);
            $orderBC->{$val . "PostalAddress"}->postalCode = $orderApi->receipt_address->zip;
            $orderBC->{$val . "PostalAddress"}->countryLetterCode = $orderApi->receipt_address->country;
            if (strlen($orderApi->receipt_address->province) > 0) {
                $orderBC->{$val . "PostalAddress"}->state = substr($orderApi->receipt_address->province, 0, 30);
            }
        }


        $this->checkAdressPostal($orderBC->shippingPostalAddress);



        if ($orderApi->settlement_currency != 'EUR') {
            $orderBC->currencyCode =  $orderApi->settlement_currency;
        }



        if (property_exists($orderApi->receipt_address, 'mobile_no')) {
            $orderBC->phoneNumber = $orderApi->receipt_address->phone_country . '-' . $orderApi->receipt_address->mobile_no;
        } elseif (property_exists($orderApi->receipt_address, 'phone_number')) {
            $orderBC->phoneNumber = $orderApi->receipt_address->phone_country . '-' . $orderApi->receipt_address->phone_number;
        }

        $orderBC->externalDocumentNumber = (string)$orderApi->id;

        $orderBC->pricesIncludeTax = true; // enables BC to do VAT autocalculation

        $orderBC->salesLines = $this->getSalesOrderLines($orderApi);
        $livraisonFees = floatval($orderApi->logistics_amount->amount);
        // ajout livraison
        $company = $this->getCompanyIntegration($orderApi);

        if ($livraisonFees > 0) {
            $account = $this->getBusinessCentralConnector($company)->getAccountForExpedition();
            $saleLineDelivery = new SaleOrderLine();
            $saleLineDelivery->lineType = SaleOrderLine::TYPE_GLACCOUNT;
            $saleLineDelivery->quantity = 1;
            $saleLineDelivery->accountId = $account['id'];
            $saleLineDelivery->unitPrice = $livraisonFees;
            $saleLineDelivery->description = 'SHIPPING FEES';
            $orderBC->salesLines[] = $saleLineDelivery;
        }


        $promotionsSeller = $this->getTotalDiscountBySeller($orderApi->child_order_list->global_aeop_tp_child_order_dto);

        // add discount
        if ($promotionsSeller > 0) {
            $account = $this->getBusinessCentralConnector($company)->getAccountByNumber('7000005');
            $saleLineDelivery = new SaleOrderLine();
            $saleLineDelivery->lineType = SaleOrderLine::TYPE_GLACCOUNT;
            $saleLineDelivery->quantity = 1;
            $saleLineDelivery->accountId = $account['id'];
            $saleLineDelivery->unitPrice = -$promotionsSeller;
            $saleLineDelivery->description = 'DISCOUNT';
            $orderBC->salesLines[] = $saleLineDelivery;
        }


        $promotionsAliExpress = $this->getTotalDiscountByAliExpress($orderApi->child_order_list->global_aeop_tp_child_order_dto);

        // add discount Aliexpress
        if ($promotionsAliExpress > 0) {
            $saleLineDelivery = new SaleOrderLine();
            $saleLineDelivery->lineType = SaleOrderLine::TYPE_COMMENT;
            $saleLineDelivery->description = 'DISCOUNT ALI EXPRESS // ' . round($promotionsAliExpress, 2) . ' EUR';
            $orderBC->salesLines[] = $saleLineDelivery;
        }
        return $orderBC;
    }



    public function checkAdressPostal(PostalAddress $postalAddress)
    {
        $street = str_replace(" ", "", strtoupper($postalAddress->street));
        $forbiddenDestinations = ['CITYBOX', 'CITIBOX', 'CITYPAQ', 'CORREOPOSTAL', 'APARTADOPOSTAL', 'SMARTPOINT'];
        if (u($street)->containsAny($forbiddenDestinations)) {
            throw new Exception("Address " . $postalAddress->street . " contains one of the forbidden word. We let you cancel the order online");
        }
    }



   


    protected function getTotalDiscountByAliExpress($saleLineApis)
    {
        return $this->getTotalDiscountBy($saleLineApis, 'PLATFORM');
    }


    protected function getTotalDiscountBySeller($saleLineApis)
    {
        return $this->getTotalDiscountBy($saleLineApis, 'SELLER');
    }

    protected function getTotalDiscountBy($saleLineApis, $typeDiscount)
    {
        $discount = 0;
        foreach ($saleLineApis as $line) {
            if ($line->child_order_discount_detail_list && property_exists($line->child_order_discount_detail_list, 'global_aeop_tp_sale_discount_info')) {
                foreach ($line->child_order_discount_detail_list->global_aeop_tp_sale_discount_info as $lineDiscount) {
                    if ($lineDiscount->promotion_owner  == $typeDiscount) {
                        $discount += floatval($lineDiscount->discount_detail->amount);
                    }
                }
            }
        }
        return $discount;
    }


    protected function getSalesOrderLines($orderApi): array
    {
        $saleOrderLines = [];
        $company = $this->getCompanyIntegration($orderApi);
        foreach ($orderApi->child_order_list->global_aeop_tp_child_order_dto as $line) {
            $saleLine = new SaleOrderLine();
            $saleLine->lineType = SaleOrderLine::TYPE_ITEM;
            $saleLine->itemId = $this->getProductCorrelationSku($line->sku_code, $company);

            $saleLine->unitPrice = floatval($line->product_price->amount);
            $saleLine->quantity = $line->product_count;

            $saleOrderLines[] = $saleLine;
        }


        return $saleOrderLines;
    }
}
