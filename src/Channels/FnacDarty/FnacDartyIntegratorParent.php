<?php

namespace App\Channels\FnacDarty;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\BusinessCentral\Model\SaleOrder;
use App\BusinessCentral\Model\SaleOrderLine;
use App\BusinessCentral\ProductTaxFinder;
use App\Channels\FnacDarty\FnacDartyApi;
use App\Helper\MailService;
use App\Service\Aggregator\ApiAggregator;
use App\Service\Aggregator\IntegratorParent;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;

abstract class FnacDartyIntegratorParent extends IntegratorParent
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
    public function integrateAllOrders()
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
                $this->addError('Problem retrieved '.$this->getChannel().' #' . $orderApi['order_id'] . ' > ' . $exception->getMessage());
            }
        }
    }


    protected function getFnacDartyApi():FnacDartyApi
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
              
        $orderBC->shipToName = $orderApi['shipping_address']['lastname']." ".$orderApi['shipping_address']['firstname'];
        if( is_string($orderApi['shipping_address']['company'])){
            $orderBC->shipToName = $orderBC->shipToName.' ('.$orderApi['shipping_address']['company'].')';
        }

        $orderBC->billToName = $orderApi['billing_address']['lastname']." ".$orderApi['billing_address']['firstname'];

        $valuesAddress = ['selling' => 'billing' , 'shipping'=>'shipping'];

        foreach ($valuesAddress as $bcVal => $fnacVal) {
            $adress =  $orderApi[$fnacVal.'_address']["address1"];
            if (is_string($orderApi[$fnacVal.'_address']['address2'])) {
                $adress .= ', ' . $orderApi[$fnacVal.'_address']["address2"];
            }
            if (is_string($orderApi[$fnacVal.'_address']['address3'])) {
                $adress .= ', ' . $orderApi[$fnacVal.'_address']["address3"];
            }
            $adress = $this->simplifyAddress($adress);

            if (strlen($adress) < 100) {
                $orderBC->{$bcVal . "PostalAddress"}->street = $adress;
            } else {
                $orderBC->{$bcVal . "PostalAddress"}->street = substr($adress, 0, 100) . "\r\n" . substr($adress, 99);
            }
            $orderBC->{$bcVal . "PostalAddress"}->city = substr($orderApi[$fnacVal.'_address']["city"], 0, 100);
            $orderBC->{$bcVal . "PostalAddress"}->postalCode = $orderApi[$fnacVal.'_address']["zipcode"];
            
            $orderBC->{$bcVal . "PostalAddress"}->countryLetterCode = substr($orderApi[$fnacVal.'_address']["country"],0,2);

            if (array_key_exists('state',$orderApi[$fnacVal.'_address']) && is_string($orderApi[$fnacVal.'_address']['state'])) {
                $orderBC->{$bcVal . "PostalAddress"}->state = substr($orderApi[$fnacVal.'_address']['state'], 0, 30);
            }
        }


        $orderBC->phoneNumber = array_key_exists('phone', $orderApi['shipping_address']) &&  is_string($orderApi['shipping_address']['phone']) ? $orderApi['shipping_address']['phone'] : null;

        $orderBC->externalDocumentNumber = $this->getExternalNumber($orderApi);
        $orderBC->pricesIncludeTax = true;

        $company = $this->getCompanyIntegration($orderApi);

        $orderBC->salesLines = [];

        $livraisonFees = 0;

        foreach ($orderApi["order_detail"] as $line) {
            $saleLine = new SaleOrderLine();
            $saleLine->lineType = SaleOrderLine::TYPE_ITEM;
            $saleLine->itemId = $this->getProductCorrelationSku($line['offer_seller_id'], $company);
            $saleLine->unitPrice = floatval($line['price']);
            $saleLine->quantity = (int)$line['quantity'];
            $orderBC->salesLines[] = $saleLine;

            if( array_key_exists('shipping_price', $line)){
                $livraisonFees += floatval($line['shipping_price']);
            }
        }
         
        // ajout livraison
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
   

  

}
