<?php

namespace App\Command\Amazon\Import;

use App\Service\Amazon\Report\AmzApiImportOrder;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportOrdersCommand extends Command
{
    protected static $defaultName = 'app:amz-import-orders';
    protected static $defaultDescription = 'Import orders from AMZ';

    public function __construct(AmzApiImportOrder $amzApiImportOrder)
    {
        $this->amzApiImportOrder = $amzApiImportOrder;
        parent::__construct();
    }

    private $amzApiImportOrder;


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dateImport = ($input->getArgument('dateIntegration') && DateTime::createFromFormat("Y-m-d", $input->getArgument('dateIntegration'))) ? DateTime::createFromFormat("Y-m-d", $input->getArgument('dateIntegration')) : null;
        $this->amzApiImportOrder->createReportAndImport($dateImport);
        return Command::SUCCESS;
    }


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('dateIntegration', InputArgument::OPTIONAL, 'Date format YYYY-MM-DD');
    }
}
