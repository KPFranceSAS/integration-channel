<?php

namespace App\Channels\Shopify\FitbitCorporate;

use App\Channels\Shopify\ShopifyStockParent;
use App\Channels\Shopify\ShopifySyncProductParent;
use App\Entity\IntegrationChannel;

class FitbitCorporateSyncProduct extends ShopifySyncProductParent
{
   


    public function syncProducts(){
        
    }


    public function getProducts(){
        $this->akeneoConnector->getAllProducts();
    }
}
