<?php

namespace App\Service\Arise;

use App\Entity\WebOrder;
use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\BusinessCentral\Model\PostalAddress;
use App\BusinessCentral\Model\SaleOrder;
use App\BusinessCentral\Model\SaleOrderLine;
use App\Helper\Integrator\IntegratorParent;
use App\Service\AliExpress\AliExpressIntegrateOrder;
use App\Service\AliExpress\AliExpressStock;
use App\Service\Arise\AriseApi;
use DateTime;
use Exception;
use function Symfony\Component\String\u;

class AriseIntegrator extends IntegratorParent
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
                $orderFull = $this->getAriseApi()->getOrder($orderApi->order_id);
                 if ($this->integrateOrder($orderFull)) {
                        $counter++;
                        $this->logger->info("Orders integrated : $counter ");
                 }
            } catch (Exception $exception) {
                $this->addError('Problem retrieved Arise #' . $orderApi->order_id . ' > ' . $exception->getMessage());
            }
        }
    }


    protected function getAriseApi():AriseApi
    {
        return $this->getApi();
    }



    protected function getOrderId($orderApi)
    {
        return $orderApi->order_id;
    }



    protected function getClientNumber(){
        return AliExpressIntegrateOrder::ALIEXPRESS_CUSTOMER_NUMBER;
    }


    public function getCustomerBC($orderApi)
    {
        return AliExpressIntegrateOrder::ALIEXPRESS_CUSTOMER_NUMBER;
    }


    public function getCompanyIntegration($orderApi)
    {
        return BusinessCentralConnector::GADGET_IBERIA;
    }



    public function transformToAnBcOrder($orderApi): SaleOrder
    {
        $orderBC = new SaleOrder();
        $orderBC->customerNumber = $this->getClientNumber();
        $datePayment = DateTime::createFromFormat('Y-m-d', substr($orderApi->created_at, 0, 10));
        $datePayment->add(new \DateInterval('P3D'));
        $orderBC->requestedDeliveryDate = $datePayment->format('Y-m-d');
        $orderBC->locationCode = $this->checkLocationCode($orderApi);
        $orderBC->billToName = $orderApi->address_billing->last_name." ".$orderApi->address_billing->first_name;
        $orderBC->shipToName = $orderApi->address_shipping->lastName." ".$orderApi->address_shipping->firstName;

        $valuesAddress = ['selling'=>'billing', 'shipping'=>'shipping'];

        foreach ($valuesAddress as $bcVal => $ariseVal) {
            $adress =  $orderApi->{'address_'.$ariseVal}->address1;
            if (strlen($orderApi->{'address_'.$ariseVal}->address2) > 0) {
                $adress .= ', ' . $orderApi->{'address_'.$ariseVal}->address2;
            }
            if (strlen($orderApi->{'address_'.$ariseVal}->address3) > 0) {
                $adress .= ', ' . $orderApi->{'address_'.$ariseVal}->address3;
            }

            $adress = $this->simplifyAddress($adress);

            if (strlen($adress) < 100) {
                $orderBC->{$bcVal . "PostalAddress"}->street = $adress;
            } else {
                $orderBC->{$bcVal . "PostalAddress"}->street = substr($adress, 0, 100) . "\r\n" . substr($adress, 99);
            }
            $orderBC->{$bcVal . "PostalAddress"}->city = substr($orderApi->{'address_'.$ariseVal}->city, 0, 100);
            if($ariseVal=='billing'){
                $orderBC->{$bcVal . "PostalAddress"}->postalCode = $orderApi->{'address_'.$ariseVal}->post_code;
            } else {
                $orderBC->{$bcVal . "PostalAddress"}->postalCode = $orderApi->{'address_'.$ariseVal}->postCode;
            }
            
            $orderBC->{$bcVal . "PostalAddress"}->countryLetterCode = 'ES';
            /*if (strlen($orderApi->{'address_'.$ariseVal}->province) > 0) {
                $orderBC->{$bcVal . "PostalAddress"}->state = substr($orderApi->{'address_'.$ariseVal}->province, 0, 30);
            }*/
        }


        $this->checkAdressPostal($orderBC->shippingPostalAddress);

        $orderBC->phoneNumber = $orderApi->address_shipping->phone;
        $orderBC->externalDocumentNumber = (string)$orderApi->order_id;
        $orderBC->pricesIncludeTax = true; // enables BC to do VAT autocalculation

        $orderBC->salesLines = $this->getSalesOrderLines($orderApi);
        $livraisonFees = floatval($orderApi->shipping_fee);
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


        $promotionsSeller = floatval($orderApi->voucher);

        // add discount
        if ($promotionsSeller > 0) {
            $account = $this->getBusinessCentralConnector($company)->getAccountByNumber('7000005');
            $saleLineDelivery = new SaleOrderLine();
            $saleLineDelivery->lineType = SaleOrderLine::TYPE_GLACCOUNT;
            $saleLineDelivery->quantity = 1;
            $saleLineDelivery->accountId = $account['id'];
            $saleLineDelivery->unitPrice = -$promotionsSeller;
            $saleLineDelivery->description = 'DISCOUNT '.$orderApi->voucher_code;
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



    public function checkLocationCode($orderApi)
    {
        $brands = [];
        foreach ($orderApi->lines as $line) {
            $brand = $this->getAriseApi()->getBrandProduct($line->product_id);
            if ($brand) {
                $this->logger->info('Brand ' . $brand);
                $brands[] = $brand;
            }
        }
        return $this->defineStockBrand($brands);
    }

    public function defineStockBrand($brands)
    {
        foreach ($brands as $brand) {
            if (in_array($brand, AliExpressStock::getBrandsFromMadrid())) {
                return WebOrder::DEPOT_MADRID;
            }
        }
        return WebOrder::DEPOT_LAROCA;
    }


    protected function getSalesOrderLines($orderApi): array
    {
        $saleOrderLines = [];
        $company = $this->getCompanyIntegration($orderApi);
        foreach ($orderApi->lines as $line) {
            $saleLine = new SaleOrderLine();
            $saleLine->lineType = SaleOrderLine::TYPE_ITEM;
            $saleLine->itemId = $this->getProductCorrelationSku($line->sku, $company);

            $saleLine->unitPrice = floatval($line->item_price);
            $saleLine->quantity = 1;
            $saleOrderLines[] = $saleLine;
        }
        return $saleOrderLines;
    }

    
	/**
	 *
	 * @return mixed
	 */
	public function getChannel() {
        return WebOrder::CHANNEL_ARISE;
	}
}
