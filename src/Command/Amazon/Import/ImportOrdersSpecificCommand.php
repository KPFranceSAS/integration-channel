<?php

namespace App\Command\Amazon\Import;

use App\Service\Amazon\Report\AmzApiImportOrder;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:amz-import-orders-specific', 'Import orders from AMZ')]
class ImportOrdersSpecificCommand extends Command
{
    public function __construct(private readonly AmzApiImportOrder $amzApiImportOrder)
    {
        parent::__construct();
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dateStartImport = DateTime::createFromFormat("Y-m-d", $input->getArgument('dateDebut'));
        $dateEndImport = DateTime::createFromFormat("Y-m-d", $input->getArgument('dateFin'));
        $this->amzApiImportOrder->createReportAndImportStartEnd($dateStartImport, $dateEndImport);
        return Command::SUCCESS;
    }


    protected function configure(): void
    {
        $this
            ->addArgument('dateDebut', InputArgument::REQUIRED, 'Date format YYYY-MM-DD')
            ->addArgument('dateFin', InputArgument::REQUIRED, 'Date format YYYY-MM-DD')
            ;
    }
}
