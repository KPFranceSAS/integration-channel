<?php

namespace App\Command\Utils;

use App\BusinessCentral\LogisticClassFinder;
use App\BusinessCentral\ProductStockFinder;
use App\Entity\Product;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckProductLogisticClassCommand extends Command
{
    protected static $defaultName = 'app:check-product-logistic';
    protected static $defaultDescription = 'Check all products logistic class';

    public function __construct(
        ManagerRegistry $managerRegistry,
        LogisticClassFinder $logisticClassFinder)
    {
        $this->logisticClassFinder = $logisticClassFinder;
        $this->manager = $managerRegistry->getManager();
        parent::__construct();
    }

    private $logisticClassFinder;

    private $manager;



    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $products = $this->manager->getRepository(Product::class)->findBy(['logisticClass'=>null]);    
        foreach($products as $product){
           $logisticClass = $this->logisticClassFinder->getBestLogisiticClass($product->getSku());
           $product->setLogisticClass($logisticClass);
        }
        $this->manager->flush();

        return Command::SUCCESS;
    }
}
