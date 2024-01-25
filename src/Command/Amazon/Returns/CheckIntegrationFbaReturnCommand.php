<?php

namespace App\Command\Amazon\Returns;

use App\Service\Amazon\Returns\CheckIntegrationFbaReturn;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckIntegrationFbaReturnCommand extends Command
{
    protected static $defaultName = 'app:amz-check-integration-returns';
    protected static $defaultDescription = 'Check integration FBA Returns';

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
