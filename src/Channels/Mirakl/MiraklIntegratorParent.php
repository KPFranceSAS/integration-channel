<?php

namespace App\Channels\Mirakl;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\BusinessCentral\Model\SaleOrder;
use App\BusinessCentral\Model\SaleOrderLine;
use App\BusinessCentral\ProductTaxFinder;
use App\Channels\Mirakl\MiraklApiParent;
use App\Entity\WebOrder;
use App\Helper\MailService;
use App\Helper\Utils\DatetimeUtils;
use App\Service\Aggregator\ApiAggregator;
use App\Service\Aggregator\IntegratorParent;
use App\Service\Carriers\UpsGetTracking;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;

abstract class MiraklIntegratorParent extends IntegratorParent
{

    public function __construct(
        ProductTaxFinder $productTaxFinder,
        ManagerRegistry $manager,
        LoggerInterface $logger,
        MailService $mailer,
        BusinessCentralAggregator $businessCentralAggregator,
        ApiAggregator $apiAggregator
    ) {
        parent::__construct($productTaxFinder, $manager, $logger, $mailer, $businessCentralAggregator, $apiAggregator);
    }




    
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

            if (array_key_exists('state', $orderApi['customer'][$miraklVal.'_address'])) {
                $orderBC->{$bcVal . "PostalAddress"}->state = substr((string) $orderApi['customer'][$miraklVal.'_address']['state'], 0, 30);
            }
        }


        $orderBC->phoneNumber = array_key_exists('phone', $orderApi['customer']['shipping_address']) ? $orderApi['customer']['shipping_address']['phone'] : null;
        $orderBC->email = $orderApi['customer_notification_email'];
        $orderBC->externalDocumentNumber = $this->getExternalNumber($orderApi);
        $orderBC->pricesIncludeTax = true;

        $orderBC->salesLines = $this->getSalesOrderLines($orderApi);

        $livraisonFees = floatval($orderApi['shipping']['price']);
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

    


    abstract protected function getExternalNumber($orderApi);
   

    protected function getSalesOrderLines($orderApi): array
    {
        $saleOrderLines = [];
        $company = $this->getCompanyIntegration($orderApi);
        foreach ($orderApi["order_lines"] as $line) {
            $saleLine = new SaleOrderLine();
            $saleLine->lineType = SaleOrderLine::TYPE_ITEM;
            $saleLine->itemId = $this->getProductCorrelationSku($line['offer']['sku'], $company);
            $saleLine->unitPrice = floatval($line['price']) / $line['quantity'];
            $saleLine->quantity = $line['quantity'];
            $saleOrderLines[] = $saleLine;
        }
        return $saleOrderLines;
    }

}
