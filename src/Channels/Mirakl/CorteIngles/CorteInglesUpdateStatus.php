<?php

namespace App\Channels\Mirakl\CorteIngles;

use App\Channels\Mirakl\MiraklUpdateStatusParent;
use App\Entity\IntegrationChannel;
use App\Entity\WebOrder;

class CorteInglesUpdateStatus extends MiraklUpdateStatusParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_CORTEINGLES;
    }



    protected function getCodeCarrier(string $carrierCode): ?string
    {
        return null;
    }


    protected function getNameCarrier(string $carrierCode): ?string
    {
        if ($carrierCode ==  WebOrder::CARRIER_DHL) {
            return "DHL";
        } elseif ($carrierCode ==  WebOrder::CARRIER_DBSCHENKER) {
            return "DB Schenker";
        } elseif ($carrierCode ==  WebOrder::CARRIER_CBL) {
            return "CBL Logistic";
        }
        return $carrierCode;
    }



    protected function updateStatusSaleOrderFulfiledBySeller(WebOrder $order)
    {
        $this->logger->info('Update status order fulfiled by seller');
        $statusSaleOrder = $this->getSaleOrderStatus($order);

        if (in_array($statusSaleOrder['statusCode'], ["99", "-1", "0", "1", "2"])) {
            if ($statusSaleOrder['statusCode']=="99" || $statusSaleOrder['statusCode']=="-1") {
                $this->addOnlyLogToOrderIfNotExists($order, 'Order status in BC > '.$statusSaleOrder['statusLabel']);
                $this->checkShipmentIsLate($order);
            } else {
                if($statusSaleOrder['statusCode']=="0") {
                    $statusLabel = 'Waiting for picking';
                } elseif($statusSaleOrder['statusCode']=="1") {
                    $statusLabel = 'Processing picking';
                } else {
                    $statusLabel = 'Ended picking';
                }
                $this->addOnlyLogToOrderIfNotExists($order, 'Order status in BC >'.$statusLabel);
            }
            $this->checkOrderIsLate($order);
            return;
        }

        if (in_array($statusSaleOrder['statusCode'], ["3", "4"])) {
            $this->addOnlyLogToOrderIfNotExists($order, 'Warehouse shipment posted in the ERP with number ' . $statusSaleOrder['ShipmentNo']);          
            $this->addOnlyLogToOrderIfNotExists($order, 'Order was prepared by warehouse and marked as fulfilled by '.$statusSaleOrder['shipmentCompany']);
                               
            $tracking = $statusSaleOrder['trackingNumber'];
            $trackingUrl = $this->trackingAggregator->getTrackingUrlBase($order->getCarrierService(), $tracking, null);
            if(strlen((string) $tracking) &&  $trackingUrl) {
                $order->setTrackingUrl($trackingUrl);
                $order->setTrackingCode($tracking);                   
                $orderApi=$order->getOrderContent();

                $result = $this->getMiraklApi()->markOrderAsFulfill(
                    $orderApi['id'],
                    $this->getCodeCarrier($order->getCarrierService()),
                    $this->getNameCarrier($order->getCarrierService()),
                    $trackingUrl,
                    $tracking
                );
                if ($result) {
                    $this->addLogToOrder($order, 'Mark as fulfilled on '.$this->getChannel());
                    $order->setErpDocument(WebOrder::DOCUMENT_INVOICE);
                    $order->setStatus(WebOrder::STATE_INVOICED);
                } else {
                    $this->addLogToOrder($order, 'Error posting tracking number ' . $tracking);
                }
            } else {
                $this->addOnlyLogToOrderIfNotExists($order, 'Tracking number is not yet retrieved from '.$order->getCarrierService().' for expedition '. $statusSaleOrder['ShipmentNo'].' / tracking is still '.$tracking);
            }
        }
    }


}
