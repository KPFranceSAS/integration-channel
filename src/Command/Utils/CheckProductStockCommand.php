<?php

namespace App\Command\Utils;

use App\BusinessCentral\ProductStockFinder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:check-product-stocks', 'Check all products stocks')]
class CheckProductStockCommand extends Command
{
    public function __construct(private readonly ProductStockFinder $productStockFinder)
    {
        parent::__construct();
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stock = $this->productStockFinder->getFinalStockProductWarehouse('ANK-PCK-4');
        $stock = $this->productStockFinder->getFinalStockProductWarehouse('FLS-PCK-STW-FLSLED');
        $stock = $this->productStockFinder->getFinalStockProductWarehouse('FLS-PCK-FLASHLEDS4');
        $stock = $this->productStockFinder->getFinalStockProductWarehouse('PX-P3D2449');
        return Command::SUCCESS;
    }
}
