<?php

namespace App\Command\Pricing;

use App\Service\Import\ImportPricingsImporter;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class StoreHistoryPricingsCommand extends Command
{
    protected static $defaultName = 'app:store-history-pricings';
    protected static $defaultDescription = 'Store history pricings';

    private $manager;

    public function __construct(ManagerRegistry $manager)
    {
         /** @var \Doctrine\ORM\EntityManagerInterface */
        $this->manager = $manager->getManager();
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $batchSize = 50;
        $i = 0;
        $newSaved = 0;
        $q = $this->manager->createQuery('select ps from App\Entity\ProductSaleChannel ps');
        foreach ($q->toIterable() as $productSaleChannel) {
            $added = $productSaleChannel->checkAndAddHistory();
            if($added){
                $newSaved++;
            }
            ++$i;
            if (($i % $batchSize) === 0) {
                $output->writeln("Saved  $i product sale channels ");
                $this->manager->flush();
                $this->manager->clear();
            }
        }
        $output->writeln("Saved  $i product sale channels and $newSaved historical added");
        $this->manager->flush();
        $this->manager->clear();
        return Command::SUCCESS;
    }
}
