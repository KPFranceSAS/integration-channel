<?php

namespace App\Channels\Shopify;

use App\Channels\Shopify\ShopifyApiParent;
use App\Entity\WebOrder;
use App\Service\Aggregator\UpdateStatusParent;
use App\Service\Carriers\DhlGetTracking;

abstract class ShopifyUpdateStatusParent extends UpdateStatusParent
{
    protected function getShopifyApi(): ShopifyApiParent
    {
        return $this->getApi();
    }




    protected function postUpdateStatusDelivery(WebOrder $order, $invoice, $trackingNumber=null)
    {
        $jsonOrder = $order->getOrderContent();
        $postCode = $invoice['shippingPostalAddress']['postalCode'];
        $result = $this->getShopifyApi()->markAsFulfilled(
            $jsonOrder['id'],
            $order->getCarrierService(),
            $trackingNumber,
            $this->trackingAggregator->getTrackingUrlBase($order->getCarrierService(), $trackingNumber, $postCode)
        );
        if ($result) {
            $this->addLogToOrder($order, 'Mark as fulfilled on ' . $this->getChannel());
            return true;
        } else {
            $this->addOnlyErrorToOrderIfNotExists($order, 'Error posting tracking number ' . $trackingNumber);
            return false;
        }
    }
}
