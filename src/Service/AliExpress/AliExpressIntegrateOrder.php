<?php

namespace App\Service\AliExpress;

use App\Entity\ProductCorrelation;
use App\Entity\WebOrder;
use App\Helper\BusinessCentral\Connector\BusinessCentralConnector;
use App\Helper\BusinessCentral\Model\SaleOrder;
use App\Helper\BusinessCentral\Model\SaleOrderLine;
use App\Service\AliExpress\AliExpressApi;
use App\Service\BusinessCentral\BusinessCentralAggregator;
use App\Service\Integrator\IntegratorParent;
use App\Service\MailService;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;
use stdClass;

class AliExpressIntegrateOrder extends IntegratorParent

{

    protected $businessCentralConnector;

    protected $aliExpress;


    public function __construct(
        ManagerRegistry $manager,
        LoggerInterface $logger,
        MailService $mailer,
        AliExpressApi $aliExpress,
        BusinessCentralAggregator $businessCentralAggregator
    ) {
        $businessCentralConnector = $businessCentralAggregator->getBusinessCentralConnector(BusinessCentralConnector::GADGET_IBERIA);
        parent::__construct($manager, $logger, $mailer, $businessCentralConnector);
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
            $orderFull = $this->aliExpress->getOrder($orderApi->order_id);
            if ($this->integrateOrder($orderFull)) {
                $counter++;
                $this->logger->info("Orders integrated : $counter ");
            }
        }
    }



    protected function getOrderId(stdClass $orderApi)
    {
        return $orderApi->id;
    }



    /**
     * Transform an order as serialized to array
     *
     * @param stdClass $order
     * @return SaleOrder
     */
    public function transformToAnBcOrder(stdClass $orderApi): SaleOrder
    {
        $orderBC = new SaleOrder();
        $orderBC->customerNumber = '002355';


        $datePayment = DateTime::createFromFormat('Y-m-d', substr($orderApi->gmt_pay_success, 0, 10));
        $datePayment->add(new \DateInterval('P3D'));
        $orderBC->requestedDeliveryDate = $datePayment->format('Y-m-d');

        $orderBC->billToName = $orderApi->receipt_address->contact_person;
        $orderBC->shipToName = $orderApi->receipt_address->contact_person;

        $valuesAddress = ['selling', 'shipping'];

        foreach ($valuesAddress as $val) {
            $orderBC->{$val . "PostalAddress"}->street = substr($orderApi->receipt_address->detail_address, 0, 100);
            if (property_exists($orderApi->receipt_address, 'address2') && strlen($orderApi->receipt_address->address2) > 0) {
                $orderBC->{$val . "PostalAddress"}->street .= "\r\n" . substr($orderApi->receipt_address->address2, 0, 100);
            }
            $orderBC->{$val . "PostalAddress"}->city = substr($orderApi->receipt_address->city, 0, 30);
            $orderBC->{$val . "PostalAddress"}->postalCode = $orderApi->receipt_address->zip;
            $orderBC->{$val . "PostalAddress"}->countryLetterCode = $orderApi->receipt_address->country;
            if (strlen($orderApi->receipt_address->province) > 0) {
                $orderBC->{$val . "PostalAddress"}->state = $orderApi->receipt_address->province;
            }
        }

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
        $orderBC->salesLines = $this->getSalesOrderLines($orderApi->child_order_list->global_aeop_tp_child_order_dto);

        $livraisonFees = floatval($orderApi->logistics_amount->amount);
        // ajout livraison 
        if ($livraisonFees > 0) {
            $account = $this->businessCentralConnector->getAccountForExpedition();
            $saleLineDelivery = new SaleOrderLine();
            $saleLineDelivery->lineType = SaleOrderLine::TYPE_GLACCOUNT;
            $saleLineDelivery->quantity = 1;
            $saleLineDelivery->accountId = $account['id'];
            $saleLineDelivery->unitPrice = $livraisonFees;
            $saleLineDelivery->description = 'SHIPPING FEES';
            $orderBC->salesLines[] = $saleLineDelivery;
        }

        //$promotionsFees = floatval($orderApi->order_discount_info->amount);
        $promotionsFees = $this->getTotalDiscount($orderApi->child_order_list->global_aeop_tp_child_order_dto);

        // add discount 
        if ($promotionsFees > 0) {
            $account = $this->businessCentralConnector->getAccountForExpedition();
            $saleLineDelivery = new SaleOrderLine();
            $saleLineDelivery->lineType = SaleOrderLine::TYPE_GLACCOUNT;
            $saleLineDelivery->quantity = 1;
            $saleLineDelivery->accountId = $account['id'];
            $saleLineDelivery->unitPrice = -$promotionsFees;
            $saleLineDelivery->description = 'DISCOUNT';
            $orderBC->salesLines[] = $saleLineDelivery;
        }


        return $orderBC;
    }


    private function getTotalDiscount($saleLineApis)
    {
        $discount = 0;
        foreach ($saleLineApis as $line) {
            foreach ($line->child_order_discount_detail_list->global_aeop_tp_sale_discount_info as $lineDiscount) {
                if ($lineDiscount->promotion_owner  == 'SELLER') {
                    $discount += floatval($lineDiscount->discount_detail->amount);
                }
            }
        }
        return $discount;
    }


    private function getSalesOrderLines(array $saleLineApis): array
    {
        $saleOrderLines = [];

        foreach ($saleLineApis as $line) {

            $saleLine = new SaleOrderLine();
            $saleLine->lineType = SaleOrderLine::TYPE_ITEM;
            $saleLine->itemId = $this->getProductCorrelationSku($line->sku_code);

            $saleLine->unitPrice = floatval($line->product_price->amount);
            $saleLine->quantity = $line->product_count;

            $saleOrderLines[] = $saleLine;
        }


        return $saleOrderLines;
    }
}
