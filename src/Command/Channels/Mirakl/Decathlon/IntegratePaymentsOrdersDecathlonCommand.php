<?php

namespace App\Command\Channels\Mirakl\Decathlon;

use App\Channels\Mirakl\Decathlon\DecathlonAccountingIntegration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:integrate-payments-decathlon', 'Integrate payments on Decathlon')]
class IntegratePaymentsOrdersDecathlonCommand extends Command
{
    public function __construct(
        private readonly DecathlonAccountingIntegration $accountingIntegration,
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $this->accountingIntegration->integrateAllSettlements();
       
        return Command::SUCCESS;
    }



}
