<?php

namespace App\Command\Pricing;

use App\Service\Import\ImportPricingsImporter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class RunImportPricingsCommand extends Command
{
    protected static $defaultName = 'app:run-import-pricings';
    protected static $defaultDescription = 'Import pricings';

    private $productImporter;

    public function __construct(ImportPricingsImporter $productImporter)
    {
        $this->productImporter = $productImporter;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->productImporter->importImportPricings();
        return Command::SUCCESS;
    }
}
