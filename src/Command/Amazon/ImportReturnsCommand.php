<?php

namespace App\Command\Amazon;

use App\Service\Amazon\AmzApiImportReturn;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportReturnsCommand extends Command
{
    protected static $defaultName = 'app:amz-import-returns';
    protected static $defaultDescription = 'Import returns from AMZ';

    public function __construct(AmzApiImportReturn $amzApiImportReturn)
    {
        $this->amzApiImportReturn = $amzApiImportReturn;
        parent::__construct();
    }

    private $amzApiImportReturn;


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
