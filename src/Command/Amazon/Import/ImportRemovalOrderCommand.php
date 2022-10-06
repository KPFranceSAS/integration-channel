<?php

namespace App\Command\Amazon\Import;

use App\Service\Amazon\Report\AmzApiImportRemovalOrder;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportRemovalOrderCommand extends Command
{
    protected static $defaultName = 'app:amz-import-removal-orders';
    protected static $defaultDescription = 'Import Removal Orders from AMZ';

    public function __construct(AmzApiImportRemovalOrder $amzApiImportRemovalOrder)
    {
        $this->amzApiImportRemovalOrder = $amzApiImportRemovalOrder;
        parent::__construct();
    }

    private $amzApiImportRemovalOrder;


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->amzApiImportRemovalOrder->createReportAndImport();
        return Command::SUCCESS;
    }
}
