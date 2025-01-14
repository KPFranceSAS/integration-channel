<?php

namespace App\Command\Channels\Shopify\Flashled;

use App\Channels\Shopify\Flashled\FlashledPaypalAccountingIntegration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:flashled-paypal', 'Intgertae Flashled paypal payout')]
class FlashledPaypalPayoutCommand extends Command
{
    public function __construct(private readonly FlashledPaypalAccountingIntegration $flashledApi)
    {
        parent::__construct();
    }



    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $this->flashledApi->integrateAllSettlements();
        

        return Command::SUCCESS;
    }
}
