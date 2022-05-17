<?php

namespace App\Helper\Invoice;

use App\Entity\WebOrder;
use App\Helper\Api\ShopifyApiParent;
use App\Helper\Invoice\InvoiceParent;

abstract class ShopifyInvoiceParent extends InvoiceParent
{

    protected function getShopifyApi(): ShopifyApiParent
    {
        return $this->getApi();
    }




    protected function postInvoice(WebOrder $order, $invoice)
    {
        $tracking = $this->getTracking($order, $invoice);
        if (!$tracking) {
            $this->logger->info('Not found tracking for invoice ' . $invoice['number']);
        } else {
            $this->addOnlyLogToOrderIfNotExists($order, 'Order was fulfilled by ' . $tracking['Carrier'] . " with tracking number " . $tracking['Tracking number']);
            $jsonOrder = $order->getOrderContent();
            $mainLocation = $this->getShopifyApi()->getMainLocation();
            foreach ($jsonOrder['line_items'] as $item) {
                $ids[] = ['id' => $item['id']];
            }
            $order->setTrackingUrl('https://clientesparcel.dhl.es/LiveTracking/ModificarEnvio/' . $tracking['Tracking number']);
            $result = $this->getShopifyApi()->markAsFulfilled($jsonOrder['id'], $mainLocation['id'], $ids, $tracking['Tracking number'], 'https://clientesparcel.dhl.es/LiveTracking/ModificarEnvio/' . $tracking['Tracking number']);
            if ($result) {
                $this->addLogToOrder($order, 'Mark as fulfilled on ' . $this->getChannel());
                return true;
            } else {
                $this->addOnlyErrorToOrderIfNotExists($order, 'Error posting tracking number ' . $tracking['Tracking number']);
            }
        }
        return false;
    }
}
