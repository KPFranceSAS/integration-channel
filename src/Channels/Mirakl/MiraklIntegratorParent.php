<?php

namespace App\Channels\Mirakl;

use App\BusinessCentral\Model\SaleOrder;
use App\BusinessCentral\Model\SaleOrderLine;
use App\Channels\Mirakl\MiraklApiParent;
use App\Service\Aggregator\IntegratorParent;
use Exception;

abstract class MiraklIntegratorParent extends IntegratorParent
{

   
    /**
     * process all invocies directory
     *
     * @return void
     */
    public function integrateAllOrders(): void
    {
        $counter = 0;
        $ordersApi = $this->getApi()->getAllOrdersToSend();

        foreach ($ordersApi as $orderApi) {
            try {
                if ($this->integrateOrder($orderApi)) {
                    $counter++;
                    $this->logger->info("Orders integrated : $counter ");
                }
            } catch (Exception $exception) {
                $this->addError('Problem retrieved '.$this->getChannel().' #' . $orderApi['id'] . ' > ' . $exception->getMessage());
            }
        }
    }


    protected function getMiraklApi():MiraklApiParent
    {
        return $this->getApi();
    }



    protected function getOrderId($orderApi)
    {
        return $this->getExternalNumber($orderApi);
    }



    abstract public function getCustomerBC($orderApi) : string;
   


    abstract public function getCompanyIntegration($orderApi): string;




    public function transformToAnBcOrder($orderApi): SaleOrder
    {
        $orderBC = new SaleOrder();
        $orderBC->customerNumber = $this->getCustomerBC($orderApi);
              
        $orderBC->shipToName = $orderApi['customer']['shipping_address']['lastname']." ".$orderApi['customer']['shipping_address']['firstname'];
        $orderBC->billToName = $orderApi['customer']['billing_address']['lastname']." ".$orderApi['customer']['billing_address']['firstname'];
        

        

        $valuesAddress = ['selling' => 'billing' , 'shipping'=>'shipping'];

        foreach ($valuesAddress as $bcVal => $miraklVal) {
            $adress =  $orderApi['customer'][$miraklVal.'_address']["street_1"];
            if (array_key_exists('street_2', $orderApi['customer'][$miraklVal.'_address'])) {
                $adress .= ', ' . $orderApi['customer'][$miraklVal.'_address']["street_2"];
            }
            $adress = $this->simplifyAddress($adress);

            if (strlen((string) $adress) < 100) {
                $orderBC->{$bcVal . "PostalAddress"}->street = $adress;
            } else {
                $orderBC->{$bcVal . "PostalAddress"}->street = substr((string) $adress, 0, 100) . "\r\n" . substr((string) $adress, 99);
            }
            $orderBC->{$bcVal . "PostalAddress"}->city = substr((string) $orderApi['customer'][$miraklVal.'_address']["city"], 0, 100);
            $orderBC->{$bcVal . "PostalAddress"}->postalCode = $orderApi['customer'][$miraklVal.'_address']["zip_code"];
            
            $orderBC->{$bcVal . "PostalAddress"}->countryLetterCode = $orderApi['customer'][$miraklVal.'_address']["country"];

            if (array_key_exists('state', $orderApi['customer'][$miraklVal.'_address']) && $orderBC->{$bcVal . "PostalAddress"}->countryLetterCode != 'FR') {
                $orderBC->{$bcVal . "PostalAddress"}->state = substr((string) $orderApi['customer'][$miraklVal.'_address']['state'], 0, 30);
            }
        }


        $orderBC->phoneNumber = array_key_exists('phone', $orderApi['customer']['shipping_address']) ? $orderApi['customer']['shipping_address']['phone'] : null;
        $orderBC->email = $orderApi['customer_notification_email'];
        $orderBC->externalDocumentNumber = $this->getExternalNumber($orderApi);
        $orderBC->pricesIncludeTax = true;

        $isTaxExcluded = $orderApi['order_tax_mode'] == 'TAX_EXCLUDED';

        $orderBC->salesLines = $this->getSalesOrderLines($orderApi, $isTaxExcluded);

        $livraisonFees =  $this->getShippingFees($orderApi, $isTaxExcluded);
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
        return $orderBC;
    }




    protected function getShippingFees($orderApi, $taxExcluded): float
    {
        $shippingFees =0;
        foreach ($orderApi["order_lines"] as $line) {
            $shippingFees += floatval($line['shipping_price']);
            if($taxExcluded && array_key_exists('shipping_taxes', $line)){
                foreach($line['shipping_taxes'] as $shippingTaxe){
                    $shippingFees += floatval($shippingTaxe['amount']);
                }
            }
        }
        return $shippingFees;
    }

    


    abstract protected function getExternalNumber($orderApi);
   

    protected function getSalesOrderLines($orderApi, $taxExcluded): array
    {
        $saleOrderLines = [];
        $company = $this->getCompanyIntegration($orderApi);
        foreach ($orderApi["order_lines"] as $line) {
            $saleLine = new SaleOrderLine();
            $saleLine->lineType = SaleOrderLine::TYPE_ITEM;
            $saleLine->itemId = $this->getProductCorrelationSku($line['offer']['sku'], $company);

            $price = $line['price'];
            if($taxExcluded && array_key_exists('taxes', $line)){
                foreach($line['taxes'] as $lineTaxe){
                    $price += floatval($lineTaxe['amount']);
                }
            }
            $saleLine->unitPrice = floatval($price) / $line['quantity'];
            $saleLine->quantity = $line['quantity'];
            $saleOrderLines[] = $saleLine;
        }
        return $saleOrderLines;
    }

}
