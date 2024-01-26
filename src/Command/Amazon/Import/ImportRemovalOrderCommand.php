<?php

namespace App\Command\Amazon\Import;

use App\Service\Amazon\Report\AmzApiImportRemovalOrder;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:amz-import-removal-orders', 'Import Removal Orders from AMZ')]
class ImportRemovalOrderCommand extends Command
{
    public function __construct(private readonly AmzApiImportRemovalOrder $amzApiImportRemovalOrder)
    {
        parent::__construct();
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->amzApiImportRemovalOrder->createReportAndImport();
        return Command::SUCCESS;
    }
}
