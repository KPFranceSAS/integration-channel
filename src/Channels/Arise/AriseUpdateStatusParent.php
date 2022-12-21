<?php

namespace App\Channels\Arise;

use App\Channels\Arise\AriseApiParent;
use App\Entity\WebOrder;
use App\Service\Aggregator\UpdateStatusParent;

abstract class AriseUpdateStatusParent extends UpdateStatusParent
{
    protected function getAriseApi(): AriseApiParent
    {
        return $this->getApi();
    }


    protected function postUpdateStatusDelivery(WebOrder $order, $invoice, $trackingNumber)
    {
        $result = $this->getAriseApi()->markOrderAsFulfill($order->getExternalNumber(), "DHL Parcel Spain", $trackingNumber);
        if ($result) {
            $this->addLogToOrder($order, 'Mark as fulfilled on Arise');
            return true;
        } else {
            $this->addLogToOrder($order, 'Error posting tracking number ' . $trackingNumber);
            return false;
        }
    }



    protected function postUpdateStatusInvoice(WebOrder $order, $invoice)
    {
        $orderArise = $this->getAriseApi()->getOrder($order->getExternalNumber());
        $packageId= null;
        foreach ($orderArise->lines as $line) {
            $packageId = $line->package_id;
        }

        $result = $this->getAriseApi()->markAsReadyToShip($packageId);
        if ($result) {
            $this->addLogToOrder($order, 'Mark as ready to ship to Arise');
            return true;
        } else {
            $this->addLogToOrder($order, 'Error posting Ready to ship');
            return false;
        }
    }



    protected function updateStatusSaleOrderFulfiledByExternal(WebOrder $order)
    {
        $this->logger->info('Update status order prepared by seller and fulfiled by external');
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
                $postUpdateStatus = $this->postUpdateStatusInvoice($order, $invoice);
                if ($postUpdateStatus) {
                    $order->setInvoiceErp($invoice['number']);
                    $order->setErpDocument(WebOrder::DOCUMENT_INVOICE);
                    $order->setStatus(WebOrder::STATE_INVOICED);
                }
            } else {
                $this->addOnlyLogToOrderIfNotExists($order, 'Invoice ' . $statusSaleOrder['InvoiceNo']." is not accesible through API");
            }
        }
    }
}
