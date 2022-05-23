<?php

namespace App\Command\Amazon\Import;

use App\Service\Amazon\Report\AmzApiImportStock;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportStockCommand extends Command
{
    protected static $defaultName = 'app:amz-import-stocks';
    protected static $defaultDescription = 'Import stocks from AMZ';

    public function __construct(AmzApiImportStock $amzApiImportStock)
    {
        $this->amzApiImportStock = $amzApiImportStock;
        parent::__construct();
    }

    private $amzApiImportStock;


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->amzApiImportStock->updateStocks();
        return Command::SUCCESS;
    }
}
