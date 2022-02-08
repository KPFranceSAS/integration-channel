<?php

namespace App\Command\Amazon;

use App\Service\Amazon\AmzApiImportReimbursement;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportReimbursementsCommand extends Command
{
    protected static $defaultName = 'app:amz-import-reimbursements';
    protected static $defaultDescription = 'Import reimbursements from AMZ';

    public function __construct(AmzApiImportReimbursement $amzApiImportReimbursement)
    {
        $this->amzApiImportReimbursement = $amzApiImportReimbursement;
        parent::__construct();
    }

    private $amzApiImportReimbursement;


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dateImport = ($input->getArgument('dateIntegration') && DateTime::createFromFormat("Y-m-d", $input->getArgument('dateIntegration'))) ? DateTime::createFromFormat("Y-m-d", $input->getArgument('dateIntegration')) : null;
        $this->amzApiImportReimbursement->createReportAndImport($dateImport);
        return Command::SUCCESS;
    }


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('dateIntegration', InputArgument::OPTIONAL, 'Date format YYYY-MM-DD');
    }
}
