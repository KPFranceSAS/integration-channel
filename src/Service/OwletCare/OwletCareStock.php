<?php

namespace App\Service\OwletCare;

use App\Entity\WebOrder;
use App\Service\BusinessCentral\BusinessCentralAggregator;
use App\Service\MailService;
use App\Service\OwletCare\OwletCareApi;
use App\Helper\Stock\StockParent;
use Doctrine\Persistence\ManagerRegistry;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;

class OwletCareStock extends StockParent

{


    public function getChannel()
    {
        return WebOrder::CHANNEL_OWLETCARE;
    }


    public function sendStocks()
    {
        $mainLocation = $this->getApi()->getMainLocation();
        $inventoLevelies = $this->getApi()->getAllInventoryLevelsFromProduct();
        foreach ($inventoLevelies as $inventoLeveli) {
            $sku = $inventoLeveli['sku'];
            $stockLevel = $this->getStockProductWarehouse($sku);
            $this->getApi()->setInventoryLevel($mainLocation['id'], $inventoLeveli['inventory_item_id'], $stockLevel);
        }
    }
}
