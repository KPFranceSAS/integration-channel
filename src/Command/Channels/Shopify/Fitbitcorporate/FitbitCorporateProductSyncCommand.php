<?php

namespace App\Command\Channels\Shopify\Fitbitcorporate;

use App\Channels\Shopify\FitbitCorporate\FitbitCorporateSyncProduct;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:fitbit-corporate-sync', 'Sync products with Shopify')]
class FitbitCorporateProductSyncCommand extends Command
{
    public function __construct(private readonly FitbitCorporateSyncProduct $fitbitCorporateSyncProduct)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->fitbitCorporateSyncProduct->syncProducts();
        return Command::SUCCESS;
    }
}
