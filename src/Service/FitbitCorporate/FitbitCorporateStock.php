<?php

namespace App\Service\FitbitCorporate;

use App\Entity\WebOrder;
use App\Helper\Stock\ShopifyStockParent;

class FitbitCorporateStock extends ShopifyStockParent
{
    public function getChannel()
    {
        return WebOrder::CHANNEL_FITBITCORPORATE;
    }
}
