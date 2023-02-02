<?php

namespace App\Channels\Mirakl;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\BusinessCentral\Model\PostalAddress;
use App\BusinessCentral\Model\SaleOrder;
use App\BusinessCentral\Model\SaleOrderLine;
use App\BusinessCentral\ProductTaxFinder;
use App\Channels\Mirakl\MiraklApiParent;
use App\Entity\WebOrder;
use App\Helper\MailService;
use App\Helper\Utils\DatetimeUtils;
use App\Service\Aggregator\ApiAggregator;
use App\Service\Aggregator\IntegratorParent;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use function Symfony\Component\String\u;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;

abstract class MiraklIntegratorParent extends IntegratorParent
{
    public const ARISE_CUSTOMER_NUMBER = "003307";


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


    protected function getMiraklApi():MiraklApiParent
    {
        return $this->getApi();
    }



    protected function getOrderId($orderApi)
    {
        return $orderApi['order_id'];
    }



    abstract public function getCustomerBC($orderApi) : string;
   


    abstract public function getCompanyIntegration($orderApi): string;




    public function transformToAnBcOrder($orderApi): SaleOrder
    {
        $orderBC = new SaleOrder();
        $orderBC->customerNumber = $this->getCustomerBC($orderApi);
        $dateDelivery = DatetimeUtils::transformFromIso8601($orderApi['delivery_date']['earliest']);
        $orderBC->requestedDeliveryDate = $dateDelivery->format('Y-m-d');
        $orderBC->locationCode = WebOrder::DEPOT_LAROCA;
      
        $orderBC->shipToName = $orderApi['shipping_address']['lastname']." ".$orderApi['shipping_address']['firstname'];
        $orderBC->billToName = $orderApi['billing_address']['lastname']." ".$orderApi['billing_address']['firstname'];
        

        $valuesAddress = ['selling' => 'billing' , 'shipping'=>'shipping'];

        foreach ($valuesAddress as $bcVal => $miraklVal) {
            $adress =  $orderApi[$miraklVal.'_address']["street_1"];
            if (strlen($orderApi[$miraklVal.'_address']["street_2"]) > 0) {
                $adress .= ', ' . $orderApi[$miraklVal.'_address']["street_2"];;
            }
            $adress = $this->simplifyAddress($adress);

            if (strlen($adress) < 100) {
                $orderBC->{$bcVal . "PostalAddress"}->street = $adress;
            } else {
                $orderBC->{$bcVal . "PostalAddress"}->street = substr($adress, 0, 100) . "\r\n" . substr($adress, 99);
            }
            $orderBC->{$bcVal . "PostalAddress"}->city = substr($orderApi[$miraklVal.'_address']["city"], 0, 100);
            $orderBC->{$bcVal . "PostalAddress"}->postalCode = $orderApi[$miraklVal.'_address']["zip_code"];
            
            $orderBC->{$bcVal . "PostalAddress"}->countryLetterCode = $orderApi[$miraklVal.'_address']["country_iso_code"];

            if (strlen($orderApi[$miraklVal.'_address']['state']) > 0) {
                $orderBC->{$bcVal . "PostalAddress"}->state = substr($orderApi[$miraklVal.'_address']['state'], 0, 30);
            }
        }


        $orderBC->phoneNumber = $orderApi['shipping_address']['phone'];
        $orderBC->email = $orderApi['customer_notification_email'];
        $orderBC->externalDocumentNumber = (string)$orderApi->order_id;
        $orderBC->pricesIncludeTax = true;

        $orderBC->salesLines = $this->getSalesOrderLines($orderApi);
        $livraisonFees = floatval($orderApi['shipping_price']);
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
        foreach ($orderApi["order_lines"] as $line) {
            $saleLine = new SaleOrderLine();
            $saleLine->lineType = SaleOrderLine::TYPE_ITEM;
            $saleLine->itemId = $this->getProductCorrelationSku($line['offer_sku'], $company);

            $saleLine->unitPrice = floatval($line['price_unit']);
            $saleLine->quantity = $line['quantity'];
            $saleOrderLines[] = $saleLine;
        }
        return $saleOrderLines;
    }


    protected function checkAfterPersist(WebOrder $order, $orderApi)
    {
       
       $accepted = $this->getMiraklApi()->markOrderAsAccepted($orderApi);
       if($accepted){
            $this->addLogToOrder($order, 'Marked as accepted on '.$this->getChannel());
        } else {
            $this->addLogToOrder($order, 'Order already accepted on '.$this->getChannel());
        }
    }

}
