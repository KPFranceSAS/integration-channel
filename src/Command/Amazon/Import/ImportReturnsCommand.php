<?php

namespace App\Command\Amazon\Import;

use App\Service\Amazon\Report\AmzApiImportReturn;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:amz-import-returns', 'Import returns from AMZ')]
class ImportReturnsCommand extends Command
{
    public function __construct(private readonly AmzApiImportReturn $amzApiImportReturn)
    {
        parent::__construct();
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dateImport = ($input->getArgument('dateIntegration') && DateTime::createFromFormat("Y-m-d", $input->getArgument('dateIntegration'))) ? DateTime::createFromFormat("Y-m-d", $input->getArgument('dateIntegration')) : null;
        $this->amzApiImportReturn->createReportAndImport($dateImport);
        return Command::SUCCESS;
    }


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('dateIntegration', InputArgument::OPTIONAL, 'Date format YYYY-MM-DD');
    }
}
