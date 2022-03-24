<?php

namespace App\Command\Utils;

use App\Entity\WebOrder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddPurchaseDateCommand extends Command
{
    protected static $defaultName = 'app:channel-add-purchase-dates';
    protected static $defaultDescription = 'Add purchase dates';

    public function __construct(ManagerRegistry $manager)
    {
        $this->manager = $manager->getManager();
        parent::__construct();
    }


    private $manager;


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $batchSize = 200;
        $i = 1;
        $q = $this->manager->createQuery('select a from App\Entity\WebOrder a where a.channel =:channel')->setParameter('channel', WebOrder::CHANNEL_CHANNELADVISOR);
        foreach ($q->toIterable() as $webOrder) {
            $orderApi = $webOrder->getOrderContent();
            $webOrder->setPurchaseDateFromString($orderApi->CreatedDateUtc);
            ++$i;
            if (($i % $batchSize) === 0) {
                $output->writeln("Saved  $i orders ");
                $this->manager->flush();
                $this->manager->clear();
            }
        }
        $output->writeln("Test  $i orders ");
        $this->manager->flush();


        return Command::SUCCESS;
    }
}
