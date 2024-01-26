<?php

namespace App\Command\Amazon\Returns;

use App\Service\Amazon\Returns\CheckIntegrationFbaReturn;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:amz-check-integration-returns', 'Check integration FBA Returns')]
class CheckIntegrationFbaReturnCommand extends Command
{
    public function __construct(private readonly CheckIntegrationFbaReturn $amzFbaReturn)
    {
        parent::__construct();
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->amzFbaReturn->checkIntegrationReturns();
        return Command::SUCCESS;
    }
}
