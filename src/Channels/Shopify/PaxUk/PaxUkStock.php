<?php

namespace App\Channels\Shopify\PaxUk;

use App\Channels\Shopify\ShopifyStockParent;
use App\Entity\IntegrationChannel;
use App\Entity\WebOrder;

class PaxUkStock extends ShopifyStockParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_PAXUK;
    }


    protected function getDefaultWarehouse()
    {
        return WebOrder::DEPOT_3PLUK;
    }


    public function getCorrelatedSku($sku)
    {
        return PaxHelper::getBusinessCentralSku($sku);
    }

}
