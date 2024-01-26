<?php

namespace App\Command\Pricing;

use App\Service\Import\ImportPricingsImporter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


#[\Symfony\Component\Console\Attribute\AsCommand('app:run-import-pricings', 'Import pricings')]
class RunImportPricingsCommand extends Command
{
    public function __construct(private readonly ImportPricingsImporter $productImporter)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->productImporter->importImportPricings();
        return Command::SUCCESS;
    }
}
