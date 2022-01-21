<?php

namespace App\Service\AliExpress;

use App\Entity\ProductCorrelation;
use App\Entity\WebOrder;
use App\Helper\BusinessCentral\Connector\BusinessCentralConnector;
use App\Helper\BusinessCentral\Model\SaleOrder;
use App\Helper\BusinessCentral\Model\SaleOrderLine;
use App\Service\AliExpress\AliExpressApi;
use App\Service\BusinessCentral\BusinessCentralAggregator;
use App\Service\MailService;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;
use stdClass;



class AliExpressIntegrateOrder

{

    private $logger;

    private $aliExpress;

    private $mailer;


    private $manager;

    private $businessCentralAggregator;

    private $businessCentralConnector;

    private $transformOrder;



    public function __construct(
        ManagerRegistry $manager,
        LoggerInterface $logger,
        MailService $mailer,
        AliExpressApi $aliExpress,
        BusinessCentralAggregator $businessCentralAggregator
    ) {
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->aliExpress = $aliExpress;
        $this->manager = $manager->getManager();
        $this->businessCentralAggregator = $businessCentralAggregator;
        $this->businessCentralConnector = $this->businessCentralAggregator->getBusinessCentralConnector(BusinessCentralConnector::GADGET_IBERIA);
    }


    /**
     * 
     * 
     * @return void
     */
    public function processOrders($reIntegrate = false)
    {
        try {
            $this->errors = [];

            if ($reIntegrate) {
                $this->logger->info('Start reintegrations');
                $this->reIntegrateAllOrders();
                $this->logger->info('Ended reintegrations');
            } else {
                $this->logger->info('Start integrations');
                $this->integrateAllOrders();
                $this->logger->info('Ended integration');
            }

            if (count($this->errors) > 0) {
                $messageError = implode('<br/>', array_unique($this->errors));
                throw new \Exception($messageError);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->mailer->sendEmail('[Order Integration AliExpress] Error', $e->getMessage());
        }
    }



    /**
     * 
     * 
     * @return void
     */
    public function reIntegrateAllOrders()
    {
        $ordersToReintegrate = $this->manager->getRepository(WebOrder::class)->findBy(
            [
                'status' => WebOrder::STATE_ERROR,
                "channel" => WebOrder::CHANNEL_ALIEXPRESS
            ]
        );
        $this->logger->info(count($ordersToReintegrate) . ' orders to re-integrate');
        foreach ($ordersToReintegrate as $orderToReintegrate) {
            $this->logLine('>>> Reintegration of order ' . $orderToReintegrate->getExternalNumber());
            $this->reIntegrateOrder($orderToReintegrate);
        }
    }





    protected function addError($errorMessage)
    {
        $this->logger->error($errorMessage);
        $this->errors[] = $errorMessage;
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





    /**
     * Integrates order 
     * Checks if already integrated in BusinessCentral (invoice or order)
     * 
     * @param stdClass $order
     * @return void
     */
    protected function integrateOrder(stdClass $order)
    {
        $this->logLine(">>> Integration order marketplace Aliexpress " . $order->id);
        if ($this->checkToIntegrateToInvoice($order->id)) {
            $this->logger->info('To integrate ');

            try {
                $webOrder = WebOrder::createOneFromAliExpress($order);
                $this->manager->persist($webOrder);

                $this->addLogToOrder($webOrder, 'Order transformation to fit to ERP model');

                $webOrder->setCompany($this->businessCentralConnector->getCompanyName());
                $orderBC = $this->transformToAnBcOrder($order);

                $this->addLogToOrder($webOrder, 'Order creation in the ERP ' . $this->businessCentralConnector->getCompanyName());

                $erpOrder = $this->businessCentralConnector->createSaleOrder($orderBC->transformToArray());

                $this->addLogToOrder($webOrder, 'Order created in the ERP ' . $this->businessCentralConnector->getCompanyName() . ' with number ' . $erpOrder['number']);
                $webOrder->setStatus(WebOrder::STATE_SYNC_TO_ERP);
                $webOrder->setOrderErp($erpOrder['number']);
                $this->addLogToOrder($webOrder, 'Integration done ' . $erpOrder['number']);
            } catch (Exception $e) {
                $message =  mb_convert_encoding($e->getMessage(), "UTF-8", "UTF-8");
                $webOrder->addError($message);
                $webOrder->setStatus(WebOrder::STATE_ERROR);
                $this->addError($message);
            }
            $this->manager->flush();
            $this->logger->info('Integration finished');
            return true;
        } else {
            $this->logger->info('No Integration');
            return false;
        }
    }



    /**
     * Integrates order 
     * Checks if already integrated in BusinessCentral (invoice or order)
     * 
     */
    public function reIntegrateOrder(WebOrder $order)
    {
        try {
            $order->cleanErrors();
            $this->addLogToOrder($order, 'Attempt to new integration');
            $this->addLogToOrder($order, 'Order transformation to fit to ERP model');
            $orderApi = $order->getOrderContent();

            $orderBC = $this->transformOrder->transformToAnBcOrder($orderApi);
            $this->addLogToOrder($order, 'Order creation in the ERP');
            $erpOrder = $this->businessCentralConnector->createSaleOrder($orderBC->transformToArray());
            $this->addLogToOrder($order, 'Order created in the ERP ' . $this->businessCentralConnector->getCompanyName() . ' with number ' . $erpOrder['number']);

            $order->setStatus(WebOrder::STATE_SYNC_TO_ERP);
            $order->setOrderErp($erpOrder['number']);
            $this->addLogToOrder($order, 'Integration done ' . $erpOrder['number']);
        } catch (Exception $e) {
            $message =  mb_convert_encoding($e->getMessage(), "UTF-8", "UTF-8");
            $order->addError($message);
            $order->setStatus(WebOrder::STATE_ERROR);
            $this->addError($message);
        }
        $this->manager->flush();
        $this->logger->info('Integration finished');
        return true;
    }


    protected function checkToIntegrateToInvoice($idOrder): bool
    {
        if ($this->isAlreadyRecordedDatabase($idOrder)) {
            $this->logger->info('Is Already Recorded Database');
            return false;
        }
        if ($this->alreadyIntegratedErp($idOrder)) {
            $this->logger->info('Is Already Recorded on ERP');
            return false;
        }
        return true;
    }


    protected function isAlreadyRecordedDatabase($idOrderApi): bool
    {
        $orderRecorded = $this->manager->getRepository(WebOrder::class)->findBy(
            [
                'externalNumber' => $idOrderApi,
                'channel' => WebOrder::CHANNEL_ALIEXPRESS
            ]
        );
        return count($orderRecorded) > 0;
    }


    protected function alreadyIntegratedErp($idOrderApi): bool
    {
        return $this->checkIfInvoice($idOrderApi) || $this->checkIfOrder($idOrderApi);
    }



    protected function checkIfOrder($idOrderApi): bool
    {
        $this->logger->info('Check order in BC ' . $idOrderApi);
        $saleOrder = $this->businessCentralConnector->getSaleOrderByExternalNumber($idOrderApi);
        return $saleOrder != null;
    }



    protected function checkIfInvoice($idOrderApi): bool
    {
        $this->logger->info('Check invoice in BC ' . $idOrderApi);
        $saleOrder = $this->businessCentralConnector->getSaleInvoiceByExternalNumber($idOrderApi);
        return $saleOrder != null;
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



    /**
     * Transform lines from Api to BC model
     *
     * @param array $saleLineApis
     * @param float $additionalCostOrDiscount
     * @return SaleOrderLine[]
     */
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




    protected function addLogToOrder(WebOrder $webOrder, $message)
    {
        $webOrder->addLog($message);
        $this->logger->info($message);
    }



    protected function logLine($message)
    {
        $separator = str_repeat("-", strlen($message));
        $this->logger->info('');
        $this->logger->info($separator);
        $this->logger->info($message);
        $this->logger->info($separator);
    }


    /**
     * Undocumented function
     *
     * @param string $sku
     * @return string
     */
    protected function getProductCorrelationSku(string $sku): string
    {
        $skuSanitized = strtoupper($sku);
        $productCorrelation = $this->manager->getRepository(ProductCorrelation::class)->findOneBy(['skuUsed' => $skuSanitized]);
        $skuFinal = $productCorrelation ? $productCorrelation->getSkuErp() : $skuSanitized;

        $product = $this->businessCentralConnector->getItemByNumber($skuFinal);
        if (!$product) {
            throw new Exception("Product with Sku $skuFinal cannot be found in business central. Check Product correlation ");
        } else {
            return  $product['id'];
        }
    }
}
