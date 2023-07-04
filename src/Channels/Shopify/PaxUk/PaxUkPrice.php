<?php

namespace App\Channels\Shopify\PaxUk;

use App\Channels\Shopify\PaxUk\PaxHelper;
use App\Channels\Shopify\ShopifyPriceParent;
use App\Entity\IntegrationChannel;
use App\Entity\Product;

class PaxUkPrice extends ShopifyPriceParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_PAXUK;
    }


    public function getProduct($skuCode): ?Product
    {
        $skuCode = PaxHelper::getBusinessCentralSku($skuCode);
        return $this->manager->getRepository(Product::class)->findOneBySku($skuCode);
    }
}
