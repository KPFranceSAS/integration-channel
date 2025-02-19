<?php

namespace App\Command\Channels\Mirakl\LeroyMerlin;

use App\Channels\Mirakl\LeroyMerlin\LeroyMerlinAccountingIntegration;
use App\Channels\Mirakl\LeroyMerlin\LeroyMerlinApi;
use App\Helper\MailService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:connect-leroymerlin', 'Connection to Leroy Merlin')]
class ConnectLeroyMerlinCommand extends Command
{
    public function __construct(
        private readonly LeroyMerlinApi $leroyMerlinApi,
        private readonly MailService $mailService,
        private readonly LeroyMerlinAccountingIntegration $leroyMerlinAccountingIntegration,
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {


       
        $this->leroyMerlinAccountingIntegration->integrateAllSettlements();
        
        return Command::SUCCESS;
    }



}
