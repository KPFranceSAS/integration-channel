<?php

namespace App\Command\Owletcare;

use App\Service\OwletCare\OwletCareApi;
use App\Service\OwletCare\OwletCareIntegrateOrder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConnectOwletCareCommand extends Command
{
    protected static $defaultName = 'app:owletcare-test';
    protected static $defaultDescription = 'Connection to owletcare test';

    public function __construct(OwletCareApi $owletCareApi, OwletCareIntegrateOrder $owletCareIntegrateOrder)
    {
        $this->owletCareApi = $owletCareApi;
        $this->owletCareIntegrateOrder = $owletCareIntegrateOrder;
        parent::__construct();
    }

    private $owletCareIntegrateOrder;

    private $owletCareApi;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $mainLocation = $this->owletCareApi->getMainLocation();
        $inventoies = $this->owletCareApi->getLevelStocksBySku($mainLocation['id']);
        dump($inventoies);

        $inventoLevelies = $this->owletCareApi->getAllInventoryLevelsFromProduct();

        foreach ($inventoLevelies as $inventoLeveli) {
            $sku =  $inventoLeveli['sku'];
            $stockLevel = $this->getStockLevelForSku($sku);
            $output->writeln("SKU $sku => $stockLevel ");
            $this->owletCareApi->setInventoryLevel($mainLocation['id'], $inventoLeveli['inventory_item_id'], $stockLevel);
        }


        $inventoies = $this->owletCareApi->getLevelStocksBySku($mainLocation['id']);
        dump($inventoies);





        return Command::SUCCESS;
    }


    private function getStockLevelForSku($sku)
    {

        return rand(25, 500);
    }
}
