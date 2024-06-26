<?php

namespace App\Command\Pricing;

use App\BusinessCentral\MsrpPriceUpdate;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:update-prices-from-bc', 'Update prices from BC')]
class UpdatePriceCommand extends Command
{
    public function __construct(protected MsrpPriceUpdate $msrpPriceUpdate)
    {
        parent::__construct();
    }





    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->msrpPriceUpdate->updateAllPrices();
        return Command::SUCCESS;
    }
}
