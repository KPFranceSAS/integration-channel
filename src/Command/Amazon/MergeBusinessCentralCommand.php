<?php

namespace App\Command\Amazon;

use App\Entity\AmazonOrder;
use App\Helper\Utils\CsvExtracter;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MergeBusinessCentralCommand extends Command
{
    protected static $defaultName = 'app:amz-merge-business';
    protected static $defaultDescription = 'Merge with invoiced';

    public function __construct(ManagerRegistry $manager, CsvExtracter $csvExtracter)
    {
        $this->csvExtracter = $csvExtracter;
        $this->manager = $manager->getManager();
        parent::__construct();
    }

    private $manager;

    private $csvExtracter;


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $orders = $this->csvExtracter->extractAssociativeDatasFromCsv($input->getArgument('file'));
        $progressPar = new ProgressBar($output, count($orders));
        $progressPar->start();
        $counter = 0;
        foreach ($orders  as $order) {
            $orderAmzs = $this->manager->getRepository(AmazonOrder::class)->findBy([
                "amazonOrderId" => $order['External Document No_'],
            ]);
            if (count($orderAmzs) > 0) {
                foreach ($orderAmzs as $orderAmz) {
                    $orderAmz->setIntegrated(true);
                    $orderAmz->setIntegrationNumber($order['No_']);
                }
            } else {
                $output->writeln('Not found ' . $order['External Document No_'], self::FAILURE);
            }
            $counter++;
            if ($counter % 50 == 0) {
                $this->manager->flush();
                $this->manager->clear();
            }
            $progressPar->advance();
        }
        $progressPar->finish();
        $this->manager->flush();
        return Command::SUCCESS;
    }


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('file', InputArgument::REQUIRED, 'Absolute path of file to import');
    }
}
