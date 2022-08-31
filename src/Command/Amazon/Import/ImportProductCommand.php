<?php

namespace App\Command\Amazon\Import;

use App\Service\Amazon\Report\AmzApiImportProduct;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportProductCommand extends Command
{
    protected static $defaultName = 'app:amz-import-products-from-inventory';
    protected static $defaultDescription = 'Import products inventory';

    public function __construct(AmzApiImportProduct $amzApiImportProduct)
    {
        $this->amzApiImportProduct = $amzApiImportProduct;
        parent::__construct();
    }

    private $amzApiImportProduct;


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->amzApiImportProduct->updateProducts();
        return Command::SUCCESS;
    }
}
