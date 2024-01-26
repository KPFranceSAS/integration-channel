<?php

namespace App\Command\Amazon\Import;

use App\Service\Amazon\Report\AmzApiImportProduct;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:amz-import-products-from-inventory', 'Import products inventory')]
class ImportProductCommand extends Command
{
    public function __construct(private readonly AmzApiImportProduct $amzApiImportProduct)
    {
        parent::__construct();
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->amzApiImportProduct->updateProducts();
        return Command::SUCCESS;
    }
}
