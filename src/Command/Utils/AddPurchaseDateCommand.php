<?php

namespace App\Command\Utils;

use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddPurchaseDateCommand extends Command
{
    protected static $defaultName = 'app:change-purchase-dates-aliexpress';
    protected static $defaultDescription = 'Add purchase dates';

    protected $manager;

    protected $logger;

    public function __construct(LoggerInterface $logger, ManagerRegistry $manager)
    {
        $this->logger = $logger;
        $this->manager = $manager->getManager();
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {


        $batchSize = 200;
        $i = 1;
        $q = $this->manager->createQuery('select a from App\Entity\WebOrder a where a.channel =:channel')->setParameter('channel', 'ALIEXPRESS');
        foreach ($q->toIterable() as $amz) {

            $purchaseDate = $amz->getPurchaseDate();
            $purchaseDate->add(new \DateInterval('PT8H'));
            $amz->setPurchaseDate($purchaseDate);

            ++$i;
            if (($i % $batchSize) === 0) {
                $this->logger->info("Saved  $i orders ");
                $this->manager->flush();
                $this->manager->clear();
            }
        }
        $this->logger->info("Test  $i orders ");
        $this->manager->flush();
        return Command::SUCCESS;
    }
}
