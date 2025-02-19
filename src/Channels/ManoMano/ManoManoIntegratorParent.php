<?php

namespace App\Channels\ManoMano;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\BusinessCentral\Model\SaleOrder;
use App\BusinessCentral\Model\SaleOrderLine;
use App\Channels\ManoMano\ManoManoApiParent;
use App\Entity\WebOrder;
use App\Service\Aggregator\IntegratorParent;
use Exception;

abstract class ManoManoIntegratorParent extends IntegratorParent
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
                $this->addError('Problem retrieved '.$this->getChannel().' #' . $orderApi['order_reference'] . ' > ' . $exception->getMessage());
            }
        }
    }


    protected function getManoManoApi():ManoManoApiParent
    {
        return $this->getApi();
    }



    protected function getOrderId($orderApi)
    {
        return $orderApi['order_reference'];
    }



    abstract public function getCustomerBC($orderApi) : string;
   


    public function getCompanyIntegration($orderApi): string
    {
        return BusinessCentralConnector::KP_FRANCE;
    }




    public function transformToAnBcOrder($orderApi): SaleOrder
    {
        $orderBC = new SaleOrder();
        $orderBC->customerNumber = $this->getCustomerBC($orderApi);
        
     
        $orderBC->shipToName = $orderApi['addresses']["shipping"]['lastname']." ".$orderApi['addresses']["shipping"]['firstname'];
        $orderBC->billToName = $orderApi['addresses']["billing"]['lastname']." ".$orderApi['addresses']["billing"]['firstname'];
        

        $valuesAddress = ['selling' => 'billing' , 'shipping'=>'shipping'];

        foreach ($valuesAddress as $bcVal => $miraklVal) {
            $adress =  $orderApi['addresses'][$miraklVal]["address_line1"];
            if (array_key_exists('address_line2', $orderApi['addresses'][$miraklVal]) && strlen((string) $orderApi['addresses'][$miraklVal]["address_line2"]) > 0) {
                $adress .= ', ' . $orderApi['addresses'][$miraklVal]["address_line2"];
            }
            $adress = $this->simplifyAddress($adress);

            if (strlen((string) $adress) < 100) {
                $orderBC->{$bcVal . "PostalAddress"}->street = $adress;
            } else {
                $orderBC->{$bcVal . "PostalAddress"}->street = substr((string) $adress, 0, 100) . "\r\n" . substr((string) $adress, 99);
            }
            $orderBC->{$bcVal . "PostalAddress"}->city = substr((string) $orderApi['addresses'][$miraklVal]["city"], 0, 100);
            $orderBC->{$bcVal . "PostalAddress"}->postalCode = $orderApi['addresses'][$miraklVal]["zipcode"];
            
            $orderBC->{$bcVal . "PostalAddress"}->countryLetterCode = $orderApi['addresses'][$miraklVal]["country_iso"];
        }


        $orderBC->phoneNumber = $orderApi['addresses']['shipping']['phone'];
        $orderBC->email = $orderApi['addresses']['billing']['email'];
        $orderBC->externalDocumentNumber = (string)$orderApi['order_reference'];
        $orderBC->pricesIncludeTax = true;

        $orderBC->salesLines = $this->getSalesOrderLines($orderApi);

        $livraisonFees = floatval($orderApi['shipping_price']['amount']);
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
   

    protected function getSalesOrderLines($orderApi): array
    {
        $saleOrderLines = [];
        $company = $this->getCompanyIntegration($orderApi);
        foreach ($orderApi["products"] as $line) {
            $saleLine = new SaleOrderLine();
            $saleLine->lineType = SaleOrderLine::TYPE_ITEM;
            $saleLine->itemId = $this->getProductCorrelationSku($line['seller_sku'], $company);

            $saleLine->unitPrice = floatval($line['product_price']['amount']);
            $saleLine->quantity = $line['quantity'];
            $saleOrderLines[] = $saleLine;
        }
        return $saleOrderLines;
    }


}
