<?php

namespace App\Command\Channels\Shopify\Minibatt;

use App\Channels\Shopify\Minibatt\MinibattPaypalAccountingIntegration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:minibatt-paypal', 'Intgertae Minibatt paypal payout')]
class MinibattPaypalPayoutCommand extends Command
{
    public function __construct(private readonly MinibattPaypalAccountingIntegration $minibattPaypalAccountingIntegration)
    {
        parent::__construct();
    }



    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $this->minibattPaypalAccountingIntegration->integrateAllSettlements();
        return Command::SUCCESS;
    }
}
