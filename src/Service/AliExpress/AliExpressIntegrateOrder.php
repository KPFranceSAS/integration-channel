<?php

namespace App\Service\AliExpress;

use App\Entity\WebOrder;
use App\Helper\BusinessCentral\Connector\BusinessCentralConnector;
use App\Helper\BusinessCentral\Model\PostalAddress;
use App\Helper\BusinessCentral\Model\SaleOrder;
use App\Helper\BusinessCentral\Model\SaleOrderLine;
use App\Service\AliExpress\AliExpressApi;
use App\Service\BusinessCentral\BusinessCentralAggregator;
use App\Service\Integrator\IntegratorParent;
use App\Service\MailService;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use function Symfony\Component\String\u;
use Psr\Log\LoggerInterface;

class AliExpressIntegrateOrder extends IntegratorParent
{
    const ALIEXPRESS_CUSTOMER_NUMBER = "002355";

    protected $businessCentralConnector;

    protected $aliExpress;


    public function __construct(
        ManagerRegistry $manager,
        LoggerInterface $logger,
        MailService $mailer,
        AliExpressApi $aliExpress,
        BusinessCentralAggregator $businessCentralAggregator
    ) {
        parent::__construct($manager, $logger, $mailer, $businessCentralAggregator);
        $this->aliExpress = $aliExpress;
    }


    public function getChannel()
    {
        return WebOrder::CHANNEL_ALIEXPRESS;
    }

    /**
     * process all invocies directory
     *
     * @return void
     */
    public function integrateAllOrders()
    {
        $counter = 0;
        $ordersApi = $this->aliExpress->getOrdersToSend();

        foreach ($ordersApi as $orderApi) {
            try {
                $orderFull = $this->aliExpress->getOrder($orderApi->order_id);
                if ($this->integrateOrder($orderFull)) {
                    $counter++;
                    $this->logger->info("Orders integrated : $counter ");
                }
            } catch (Exception $exception) {
                $this->addError('Problem retrieved Aliexpress #' . $orderApi->order_id . ' > ' . $exception->getMessage());
            }
        }
    }



    protected function getOrderId($orderApi)
    {
        return $orderApi->id;
    }



    protected function getClientNumber()
    {
        return self::ALIEXPRESS_CUSTOMER_NUMBER;
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
        $datePayment->add(new \DateInterval('P3D'));
        $orderBC->requestedDeliveryDate = $datePayment->format('Y-m-d');
        $orderBC->locationCode = $this->checkLocationCode($orderApi);
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



    public function checkLocationCode($orderApi)
    {

        $brands = [];
        foreach ($orderApi->child_order_list->global_aeop_tp_child_order_dto as $line) {
            $brand = $this->aliExpress->getBrandProduct($line->product_id);
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
        return WebOrder::DEPOT_CENTRAL;
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
