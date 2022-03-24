<?php

namespace App\Command\Utils;

use App\Entity\WebOrder;
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

        $amzs = $this->manager->getRepository(WebOrder::class)->findBy(['channel' => 'ALIEXPRESS']);
        foreach ($amzs as $amz) {
            $purchaseDate = clone ($amz->getPurchaseDate());
            $purchaseDate->add(new \DateInterval('PT8H'));
            $amz->setPurchaseDate($purchaseDate);
        }
        $this->manager->flush();

        return Command::SUCCESS;
    }
}
