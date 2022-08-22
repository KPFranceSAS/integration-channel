<?php

namespace App\Command\Amazon;

use App\Entity\Product;
use App\Entity\ProductStockDaily;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StoreDailyStockCommand extends Command
{
    protected static $defaultName = 'app:amz-store-daily-stocks';
    protected static $defaultDescription = 'Store daily stocks';

    public function __construct(ManagerRegistry $manager)
    {
        $this->manager = $manager->getManager();
        parent::__construct();
    }

    private $manager;


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $products = $this->manager->getRepository(Product::class)->findAll();
        $progressPar = new ProgressBar($output, count($products));
        $progressPar->start();
        $dateToday= new DateTime();
        foreach ($products as $product) {
            $stockDaily = ProductStockDaily::buildOneFromProduct($product);
            $stockDaily->setStockDate($dateToday);
            $this->manager->persist($stockDaily);
            if ($progressPar->getProgress() % 100 == 0) {
                $this->manager->flush();
            }
            $progressPar->advance();
        }
        $this->manager->flush();
        return Command::SUCCESS;
    }
}
