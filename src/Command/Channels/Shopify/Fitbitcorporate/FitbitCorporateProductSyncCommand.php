<?php

namespace App\Command\Channels\Shopify\Fitbitcorporate;

use App\Channels\Shopify\FitbitCorporate\FitbitCorporateSyncProduct;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FitbitCorporateProductSyncCommand extends Command
{
    protected static $defaultName = 'app:fitbit-corporate-sync';
    protected static $defaultDescription = 'Sync products with Shopify';

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
