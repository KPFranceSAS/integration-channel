<?php

namespace App\Channels\Arise;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\BusinessCentral\Model\PostalAddress;
use App\BusinessCentral\Model\SaleOrder;
use App\BusinessCentral\Model\SaleOrderLine;
use App\BusinessCentral\ProductTaxFinder;
use App\Channels\AliExpress\AliExpress\AliExpressIntegrateOrder;
use App\Channels\Arise\AriseApiParent;
use App\Entity\WebOrder;
use App\Helper\MailService;
use App\Service\Aggregator\ApiAggregator;
use App\Service\Aggregator\IntegratorParent;
use App\Service\Aggregator\StockParent;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use function Symfony\Component\String\u;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;

abstract class AriseIntegratorParent extends IntegratorParent
{
    public const ARISE_CUSTOMER_NUMBER = AliExpressIntegrateOrder::ALIEXPRESS_CUSTOMER_NUMBER; //"003307";


    public function __construct(
        ProductTaxFinder $productTaxFinder, 
        ManagerRegistry $manager, 
        LoggerInterface $logger, 
        MailService $mailer,
        BusinessCentralAggregator $businessCentralAggregator, 
        ApiAggregator $apiAggregator,
        FilesystemOperator $ariseLabelStorage
    )
    {
        parent::__construct($productTaxFinder, $manager, $logger, $mailer, $businessCentralAggregator, $apiAggregator);
        $this->ariseLabelStorage = $ariseLabelStorage;
    }


    protected $ariseLabelStorage;

    
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


    protected function getAriseApi():AriseApiParent
    {
        return $this->getApi();
    }



    protected function getOrderId($orderApi)
    {
        return $orderApi->order_id;
    }



    public function getCustomerBC($orderApi)
    {
        return AriseIntegratorParent::ARISE_CUSTOMER_NUMBER;
    }


    public function getCompanyIntegration($orderApi)
    {
        return BusinessCentralConnector::GADGET_IBERIA;
    }



    public function transformToAnBcOrder($orderApi): SaleOrder
    {
        $orderBC = new SaleOrder();
        $orderBC->customerNumber = $this->getCustomerBC($orderApi);
        $datePayment = DateTime::createFromFormat('Y-m-d', substr($orderApi->created_at, 0, 10));
        $datePayment->add(new \DateInterval('P3D'));
        $orderBC->requestedDeliveryDate = $datePayment->format('Y-m-d');
        $orderBC->locationCode = WebOrder::DEPOT_LAROCA;

        $bilingIndex= (strlen($orderApi->address_billing->city)==0) ? 'shipping' : 'billing';
        $orderBC->shipToName = $orderApi->address_shipping->last_name." ".$orderApi->address_shipping->first_name;
        if ($bilingIndex == 'billing') {
            $orderBC->billToName = $orderApi->{"address_".$bilingIndex}->last_name." ".$orderApi->{"address_".$bilingIndex}->first_name;
        } else {
            $orderBC->billToName  = $orderBC->shipToName;
        }

        $valuesAddress = ['selling'=>$bilingIndex, 'shipping'=>'shipping'];

        foreach ($valuesAddress as $bcVal => $ariseVal) {
            $adress =  $orderApi->{'address_'.$ariseVal}->address1;
            if (strlen($orderApi->{'address_'.$ariseVal}->address2) > 0) {
                $adress .= ', ' . $orderApi->{'address_'.$ariseVal}->address2;
            }
            

            $adress = $this->simplifyAddress($adress);

            if (strlen($adress) < 100) {
                $orderBC->{$bcVal . "PostalAddress"}->street = $adress;
            } else {
                $orderBC->{$bcVal . "PostalAddress"}->street = substr($adress, 0, 100) . "\r\n" . substr($adress, 99);
            }
            $orderBC->{$bcVal . "PostalAddress"}->city = substr($orderApi->{'address_'.$ariseVal}->city, 0, 100);
            $orderBC->{$bcVal . "PostalAddress"}->postalCode = $orderApi->{'address_'.$ariseVal}->post_code;
            
            $orderBC->{$bcVal . "PostalAddress"}->countryLetterCode = 'ES';

            if (strlen($orderApi->{'address_'.$ariseVal}->address3) > 0) {
                $orderBC->{$bcVal . "PostalAddress"}->state = substr($orderApi->{'address_'.$ariseVal}->address3, 0, 30);
            }
        }


        if ($this->checkIsAriseFulfilled($orderApi)) {
            $orderBC->shippingAgent = 'ARISE';
            $orderBC->shippingAgentService = 'STANDARD';
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


        $discount = 0;
        $discountPlatform = 0;
        foreach ($orderApi->lines as $line) {
            $promotionsSeller = floatval($line->voucher_seller);
            if ($promotionsSeller> 0) {
                $discount+= $promotionsSeller;
            }

            $promotionsPlateform = floatval($line->voucher_platform);
            if ($promotionsPlateform> 0) {
                $discountPlatform+= $promotionsPlateform;
            }
        }

        // add discount
        if ($discount > 0) {
            $account = $this->getBusinessCentralConnector($company)->getAccountByNumber('7000005');
            $saleLineDelivery = new SaleOrderLine();
            $saleLineDelivery->lineType = SaleOrderLine::TYPE_GLACCOUNT;
            $saleLineDelivery->quantity = 1;
            $saleLineDelivery->accountId = $account['id'];
            $saleLineDelivery->unitPrice = -$discount;
            $saleLineDelivery->description = 'DISCOUNT SELLER '.$orderApi->voucher_code_seller;
            $orderBC->salesLines[] = $saleLineDelivery;
        }


        if ($promotionsPlateform > 0) {
            $saleLineDelivery = new SaleOrderLine();
            $saleLineDelivery->lineType = SaleOrderLine::TYPE_COMMENT;
            $saleLineDelivery->description = 'DISCOUNT ARISE // ' . round($promotionsPlateform, 2) . ' EUR';
            $orderBC->salesLines[] = $saleLineDelivery;
        }

        return $orderBC;
    }

    public function checkIsAriseFulfilled($orderApi)
    {
        $isFulfilledByArise = false;
        foreach ($orderApi->lines as $line) {
            if ($line->delivery_option_sof==1) {
                $isFulfilledByArise = false;
            } else {
                $isFulfilledByArise = true;
            }
        }
        return  $isFulfilledByArise;
    }


    public function checkAdressPostal(PostalAddress $postalAddress)
    {
        $street = str_replace(" ", "", strtoupper($postalAddress->street));
        $forbiddenDestinations = ['CITYBOX', 'CITIBOX', 'CITYPAQ', 'CORREOPOSTAL', 'APARTADOPOSTAL', 'SMARTPOINT'];
        if (u($street)->containsAny($forbiddenDestinations)) {
            throw new Exception("Address " . $postalAddress->street . " contains one of the forbidden word. We let you cancel the order online");
        }
    }



    protected function checkAfterIntegration(WebOrder $order, $orderApi)
    {
        if ($order->isFulfiledBySeller()==false) {
            $this->addLogToOrder($order, 'Creation of the label for printing');
            $pdfLink = $this->getAriseApi()->createLabel($orderApi->order_id);
            $this->addLogToOrder($order, 'Get content of the label for '.$pdfLink);
            $pdfContent = file_get_contents($pdfLink);

            
            $filename = str_replace("/", "-", $order->getOrderErp()).'_'.$orderApi->order_id.'_'.date('YmdHis').'.pdf';
            $this->ariseLabelStorage->write($filename, $pdfContent);
            $link = "https://marketplace.kps-group.com/labels/".$filename;
            $this->addLogToOrder($order, 'Publish label on '.$link);
            $this->addLogToOrder($order, 'Update sale order adding the label for printing');

            $company = $this->getCompanyIntegration($orderApi);
            $bcConnector = $this->getBusinessCentralConnector($company);
            
            //$saleOrderBc = $bcConnector->getSaleOrderByNumber($order->getOrderErp());
            //$bcConnector->updateSaleOrder($saleOrderBc['id'], $saleOrderBc['@odata.etag'], ["URLEtiqueta" => $link]);
            $this->addLogToOrder($order, 'Updated sale order adding the label for printing');
        }
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
}
