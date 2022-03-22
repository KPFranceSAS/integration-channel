<?php

namespace App\Command\Amazon;

use App\Service\Amazon\AmzApiImportProduct;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportProductCommand extends Command
{
    protected static $defaultName = 'app:amz-import-products-inventory';
    protected static $defaultDescription = 'Import products inventory';

    public function __construct(AmzApiImportProduct $amzApiImportProduct)
    {
        $this->amzApiImportProduct = $amzApiImportProduct;
        parent::__construct();
    }

    private $amzApiImportProduct;


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dateImport = ($input->getArgument('dateIntegration') && DateTime::createFromFormat("Y-m-d", $input->getArgument('dateIntegration'))) ? DateTime::createFromFormat("Y-m-d", $input->getArgument('dateIntegration')) : null;
        $this->amzApiImportProduct->createReportAndImport($dateImport);
        return Command::SUCCESS;
    }


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('dateIntegration', InputArgument::OPTIONAL, 'Date format YYYY-MM-DD');
    }
}
