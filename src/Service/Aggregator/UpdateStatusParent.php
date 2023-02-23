<?php

namespace App\Service\Aggregator;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\Entity\WebOrder;
use App\Helper\MailService;
use App\Helper\Traits\TraitServiceLog;
use App\Helper\Utils\DatetimeUtils;
use App\Service\Aggregator\ApiAggregator;
use App\Service\Carriers\DhlGetTracking;
use App\Service\Carriers\TrackingAggregator;
use App\Service\Carriers\UpsGetTracking;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;

abstract class UpdateStatusParent
{
    use TraitServiceLog;

    protected $logger;

    protected $manager;

    protected $mailer;

    protected $businessCentralAggregator;

    protected $tracker;

    protected $apiAggregator;

    protected $trackingAggregator;

    protected $errors;


    public function __construct(
        ManagerRegistry $manager, 
        LoggerInterface $logger, 
        MailService $mailer, 
        BusinessCentralAggregator $businessCentralAggregator, 
        ApiAggregator $apiAggregator,
        TrackingAggregator $trackingAggregator
    )
    {
        $this->logger = $logger;
        $this->manager = $manager->getManager();
        $this->mailer = $mailer;
        $this->businessCentralAggregator = $businessCentralAggregator;
        $this->apiAggregator = $apiAggregator;
        $this->trackingAggregator = $trackingAggregator;
    }


    abstract public function getChannel();

    public function getApi()
    {
        return $this->apiAggregator->getApi($this->getChannel());
    }


    public function getBusinessCentralConnector($companyName)
    {
        return $this->businessCentralAggregator->getBusinessCentralConnector($companyName);
    }

    /**
     *
     *
     * @return void
     */
    public function updateStatusSales($reprocess = false)
    {
        try {
            $this->errors = [];
            if ($reprocess) {
                $this->logger->info('Start updating sale orders ' . $this->getChannel() . ' in error');
                $this->reUpdateStatusSaleOrders();
                $this->logger->info('Ended updating sale orders ' . $this->getChannel() . ' in error');
            } else {
                $this->logger->info('Start updating sale orders ' . $this->getChannel());
                $this->updateStatusSaleOrders();
                $this->logger->info('Ended updating sale orders ' . $this->getChannel());
            }


            if (count($this->errors) > 0) {
                throw new Exception(implode('<br/><br/>', $this->errors));
            }
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->mailer->sendEmailChannel($this->getChannel(), 'Status updates', $e->getMessage());
        }
    }


    protected function postUpdateStatusDelivery(WebOrder $order, $invoice, $trackingNumber=null)
    {
        return true;
    }


    protected function postUpdateStatusInvoice(WebOrder $order, $invoice)
    {
        return true;
    }

    protected function updateStatusSaleOrder(WebOrder $order)
    {
        try {
            if ($order->isFulfiledBySeller()) {
                $this->updateStatusSaleOrderFulfiledBySeller($order);
            } else {
                $this->updateStatusSaleOrderFulfiledByExternal($order);
            }
        } catch (Exception $e) {
            $message =  mb_convert_encoding($e->getMessage(), "UTF-8", "UTF-8");
            $this->addErrorToOrder($order, $order->getExternalNumber() . ' >> ' . $message);
        }
        $this->manager->flush();
    }

    protected function updateStatusSaleOrderFulfiledBySeller(WebOrder $order)
    {
        $this->logger->info('Update status order fulfiled by seller');
        $statusSaleOrder = $this->getSaleOrderStatus($order);

        if (in_array($statusSaleOrder['statusCode'], ["99", "-1", "0", "1", "2"])) {
            $this->addOnlyLogToOrderIfNotExists($order, 'Order status in BC >'.$statusSaleOrder['statusLabel'] .' statusCode '.$statusSaleOrder['statusCode']);
            if ($statusSaleOrder['statusCode']=="99" || $statusSaleOrder['statusCode']=="-1") {
                $this->checkShipmentIsLate($order);
            }
            $this->checkOrderIsLate($order);
            return;
        }

        if (in_array($statusSaleOrder['statusCode'], ["3", "4"]) && strlen($statusSaleOrder['InvoiceNo'])) {
            $this->addOnlyLogToOrderIfNotExists($order, 'Warehouse shipment created in the ERP with number ' . $statusSaleOrder['ShipmentNo']);
            $this->addOnlyLogToOrderIfNotExists($order, 'Invoice created in the ERP with number ' . $statusSaleOrder['InvoiceNo']);
            $businessCentralConnector   = $this->getBusinessCentralConnector($order->getCompany());
            $invoice =  $businessCentralConnector->getSaleInvoiceByNumber($statusSaleOrder['InvoiceNo']);
            if ($invoice) {
                $order->cleanErrors();
                $postUpdateStatus = false;
                if ($order->getCarrierService() == WebOrder::CARRIER_DHL) {
                    $tracking =  DhlGetTracking::getTrackingExternalWeb($statusSaleOrder['ShipmentNo']);
                    if (!$tracking) {
                        $this->addOnlyLogToOrderIfNotExists($order, 'Tracking number is not yet retrieved from DHL for expedition '. $statusSaleOrder['ShipmentNo']);
                    } else {
                        $this->addOnlyLogToOrderIfNotExists($order, 'Order was fulfilled by DHL with tracking number ' . $tracking);
                        $order->setTrackingUrl(DhlGetTracking::getTrackingUrlBase($tracking));
                        $order->setTrackingCode($tracking);
                        $postUpdateStatus = $this->postUpdateStatusDelivery($order, $invoice, $tracking);
                    }
                }


                if ($order->getCarrierService() == WebOrder::CARRIER_ARISE) {
                    $this->addOnlyLogToOrderIfNotExists($order, 'Order was prepared by warehouse and waiting to be collected by Arise');
                    $postUpdateStatus = $this->postUpdateStatusDelivery($order, $invoice);
                }


                if ($order->getCarrierService() == WebOrder::CARRIER_UPS) {
                        $tracking = $statusSaleOrder['trackingNumber'];
                        $this->addOnlyLogToOrderIfNotExists($order, 'Order was fulfilled by UPS with tracking number ' . $tracking);
                        $order->setTrackingUrl(UpsGetTracking::getTrackingUrlBase($tracking));
                        $order->setTrackingCode($tracking);
                        $postUpdateStatus = $this->postUpdateStatusDelivery($order, $invoice, $tracking);
                }



                if ($postUpdateStatus) {
                    $order->setInvoiceErp($invoice['number']);
                    $order->setErpDocument(WebOrder::DOCUMENT_INVOICE);
                    $order->setStatus(WebOrder::STATE_INVOICED);
                } else {
                    $this->checkInvoiceIsLate($order, $invoice);
                }
            } else {
                $this->addOnlyLogToOrderIfNotExists($order, 'Invoice ' . $statusSaleOrder['InvoiceNo']." is not accesible through API");
            }
        }
    }


    

    protected function updateStatusSaleOrderFulfiledByExternal(WebOrder $order)
    {
        $this->logger->info('Update status order fulfiled by External');
        $statusSaleOrder = $this->getSaleOrderStatus($order);
        $isOrderHasBeenInvoiced = $statusSaleOrder && strlen($statusSaleOrder['InvoiceNo']) > 0;
        if ($isOrderHasBeenInvoiced) {
            $this->addOnlyLogToOrderIfNotExists($order, 'Invoice created in the ERP with number ' . $statusSaleOrder['InvoiceNo']);
                
            $businessCentralConnector   = $this->getBusinessCentralConnector($order->getCompany());
            $invoice =  $businessCentralConnector->getSaleInvoiceByNumber($statusSaleOrder['InvoiceNo']);
            if ($invoice) {
                $order->cleanErrors();
                $postUpdateStatus = $this->postUpdateStatusInvoice($order, $invoice);
                if ($postUpdateStatus) {
                    $order->setInvoiceErp($invoice['number']);
                    $order->setErpDocument(WebOrder::DOCUMENT_INVOICE);
                    $order->setStatus(WebOrder::STATE_COMPLETE);
                }
            } else {
                $this->addOnlyLogToOrderIfNotExists($order, 'Invoice n°' . $statusSaleOrder['InvoiceNo'].' is not yet accessible through API');
            }
        } else {
            $this->checkOrderIsLate($order);
        }
    }


    protected function getSaleOrderStatus(WebOrder $order)
    {
        $businessCentralConnector   = $this->getBusinessCentralConnector($order->getCompany());
        $status =  $businessCentralConnector->getStatusOrderByNumber($order->getOrderErp());
        if ($status) {
            $this->logger->info("Status found by reference to the order number " . $order->getOrderErp());
            return reset($status['statusOrderLines']);
        }

        $this->logger->info("Status not found for moment " . $order->getOrderErp() . " for " . $order->getCustomerNumber());
        return null;
    }



    protected function getInvoiceNumber(WebOrder $order)
    {
        $businessCentralConnector   = $this->getBusinessCentralConnector($order->getCompany());
        $invoice =  $businessCentralConnector->getSaleInvoiceByOrderNumber($order->getOrderErp());
        if ($invoice) {
            $this->logger->info("Invoice found by reference to the order number " . $order->getOrderErp());
            return $invoice;
        }

        $invoice =  $businessCentralConnector->getSaleInvoiceByExternalDocumentNumberCustomer($order->getExternalNumber(), $order->getCustomerNumber());

        if ($invoice) {
            $this->logger->info("Invoice found by reference to the order external " . $order->getExternalNumber() . " and customer number " . $order->getCustomerNumber());
            return $invoice;
        }

        $this->logger->info("Invoice not found for moment " . $order->getOrderErp() . " for " . $order->getCustomerNumber());
        return null;
    }



    public function checkShipmentIsLate(WebOrder $order)
    {
        $this->logger->info('Check if shipment is late ');
        $businessCentralConnector   = $this->getBusinessCentralConnector($order->getCompany());
        $orderBc =  $businessCentralConnector->getSaleOrderByNumber($order->getOrderErp());
        $documentDate = DatetimeUtils::transformFromIso8601($orderBc['lastModifiedDateTime']);
        $now = new DateTime();
        $interval = $now->diff($documentDate, true);
        $nbHours = $interval->format('%a') * 24 + $interval->format('%h');
        if ($nbHours > 2) {
            $this->addOnlyErrorToOrderIfNotExists($order, $order . ' has been created but warehouse shipment has not been created for  ' . $order->getOrderErp() . '. Please check what is delaying  release of the sale order');
        }
    }



    protected function checkOrderIsLate(WebOrder $order)
    {
        $this->logger->info('Check if order is late ');
        if ($order->hasDelayTreatment()) {
            $messageDelay = $order->getDelayProblemMessage();
            $this->addOnlyErrorToOrderIfNotExists($order, $messageDelay);
        }
    }


    public function checkInvoiceIsLate(WebOrder $order, $invoice)
    {
        $this->logger->info('Check if late ' . $invoice['number'] . " >> " . $invoice['invoiceDate']);
        $invoiceDate = DateTime::createFromFormat('Y-m-d H:i', $invoice['invoiceDate'] . ' 18:00');
        $now = new DateTime();
        $interval = $now->diff($invoiceDate, true);
        $nbHours = $interval->format('%a') * 24 + $interval->format('%h');
        if ($nbHours > 30) {
            $this->addOnlyErrorToOrderIfNotExists($order, $order . ' has been sent with the invoice ' . $invoice['number'] . ' but no tracking is retrieved. Please confirm tracking on ' . $this->getChannel());
        }
    }



    /**
     *
     *
     * @return void
     */
    protected function updateStatusSaleOrders()
    {
        /** @var array[\App\Entity\WebOrder] */
        $ordersToSend = $this->manager->getRepository(WebOrder::class)->findBy(
            [
                "status" => WebOrder::STATE_SYNC_TO_ERP,
                "channel" => $this->getChannel(),
            ]
        );
        $this->logger->info(count($ordersToSend) . ' sale orders to update');
        foreach ($ordersToSend as $orderToSend) {
            $this->logLine('>>> Update sale Order '.$orderToSend->getChannel().' '. $orderToSend->getExternalNumber());
            $this->updateStatusSaleOrder($orderToSend);
        }
    }

    /**
     *
     *
     * @return void
     */
    protected function reUpdateStatusSaleOrders()
    {
        /** @var array[\App\Entity\WebOrder] */
        $ordersToSend = $this->manager->getRepository(WebOrder::class)->findBy(
            [
                "status" => WebOrder::STATE_ERROR_INVOICE,
                "channel" => $this->getChannel(),
            ]
        );
        $this->logger->info(count($ordersToSend) . ' orders to re-send');
        foreach ($ordersToSend as $orderToSend) {
            $this->logLine('>>> Updating status of order ' . $orderToSend->getExternalNumber());
            $this->updateStatusSaleOrder($orderToSend);
        }
    }
}
