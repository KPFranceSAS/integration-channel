<?php

namespace App\Command\Amazon\Import;

use App\Service\Amazon\Report\AmzApiImportStock;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:amz-import-stocks', 'Import stocks from AMZ')]
class ImportStockCommand extends Command
{
    public function __construct(private readonly AmzApiImportStock $amzApiImportStock)
    {
        parent::__construct();
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->amzApiImportStock->updateStocks();
        return Command::SUCCESS;
    }
}
