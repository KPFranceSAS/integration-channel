<?php

namespace App\Channels\Shopify\PaxEu;

use App\Channels\Shopify\PaxUk\PaxHelper;
use App\Channels\Shopify\ShopifyStockParent;
use App\Entity\IntegrationChannel;
use App\Entity\WebOrder;

class PaxEuStock extends ShopifyStockParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_PAXEU;
    }


    protected function getDefaultWarehouse()
    {
        return WebOrder::DEPOT_LAROCA;
    }


    public function getCorrelatedSku($sku)
    {
        return PaxHelper::getBusinessCentralSku($sku);
    }

}
