<?php

namespace App\Command\Amazon;

use App\Service\Amazon\AmzApiImport;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportOrdersCommand extends Command
{
    protected static $defaultName = 'app:amz-import-orders';
    protected static $defaultDescription = 'Import orders from AMZ';

    public function __construct(AmzApiImport $amzApiImport)
    {
        $this->amzApiImport = $amzApiImport;
        parent::__construct();
    }

    private $amzApiImport;


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dateImport = ($input->getArgument('dateIntegration') && DateTime::createFromFormat("Y-m-d", $input->getArgument('dateIntegration'))) ? DateTime::createFromFormat("Y-m-d", $input->getArgument('dateIntegration')) : null;
        $this->amzApiImport->createReportOrdersAndImport($dateImport);
        return Command::SUCCESS;
    }


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('dateIntegration', InputArgument::OPTIONAL, 'Date format YYYY-MM-DD');
    }
}
