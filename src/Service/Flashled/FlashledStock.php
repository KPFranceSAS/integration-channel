<?php

namespace App\Service\Flashled;

use App\Entity\WebOrder;
use App\Helper\Stock\ShopifyStockParent;

class FlashledStock extends ShopifyStockParent
{
    public function getChannel()
    {
        return WebOrder::CHANNEL_FLASHLED;
    }


    public function initializeStockLevels()
    {
        parent::initializeStockLevels();
        $key = 'FL-FLASHLED-SOS' . '_' . WebOrder::DEPOT_LAROCA;
        $this->stockLevels[$key] = 200;



        return $this->stockLevels;
    }
}
