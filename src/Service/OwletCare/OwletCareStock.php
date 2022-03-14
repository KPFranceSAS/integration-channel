<?php

namespace App\Service\OwletCare;

use App\Entity\WebOrder;
use App\Service\BusinessCentral\BusinessCentralAggregator;
use App\Service\MailService;
use App\Service\OwletCare\OwletCareApi;
use App\Service\Stock\StockParent;
use Doctrine\Persistence\ManagerRegistry;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;

class OwletCareStock extends StockParent

{

    protected $businessCentralConnector;

    protected $owletCareApi;


    public function __construct(
        FilesystemOperator $awsStorage,
        ManagerRegistry $manager,
        LoggerInterface $logger,
        MailService $mailer,
        OwletCareApi $owletCareApi,
        BusinessCentralAggregator $businessCentralAggregator
    ) {
        parent::__construct($awsStorage, $manager, $logger, $mailer, $businessCentralAggregator);
        $this->owletCareApi = $owletCareApi;
    }


    public function getChannel()
    {
        return WebOrder::CHANNEL_OWLETCARE;
    }

    /**
     * process all invocies directory
     *
     * @return void
     */
    public function sendStocks()
    {
        $mainLocation = $this->owletCareApi->getMainLocation();
        $inventoLevelies = $this->owletCareApi->getAllInventoryLevelsFromProduct();
        foreach ($inventoLevelies as $inventoLeveli) {
            $sku = $inventoLeveli['sku'];
            $stockLevel = $this->getStockProductWarehouse($sku);
            $this->owletCareApi->setInventoryLevel($mainLocation['id'], $inventoLeveli['inventory_item_id'], $stockLevel);
        }
    }
}
