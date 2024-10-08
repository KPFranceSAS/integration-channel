<?php

namespace App\Command\Channels\Mirakl\LeroyMerlin;

use App\Channels\Mirakl\LeroyMerlin\LeroyMerlinAccountingIntegration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:integrate-payments-leroymerlin', 'Integrate payments on Leroy Merlin')]
class IntegratePaymentsOrdersLeroyMerlinCommand extends Command
{
    public function __construct(
        private readonly LeroyMerlinAccountingIntegration $accountingIntegration,
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $this->accountingIntegration->integrateAllSettlements();
       
        return Command::SUCCESS;
    }



}
