<?php

namespace App\Command\Amazon\Returns;

use App\Service\Amazon\Returns\CheckIntegrationFbaReturn;
use App\Service\Amazon\Returns\UpdateAmzFbaReturn;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckIntegrationFbaReturnCommand extends Command
{
    protected static $defaultName = 'app:amz-check-integration-returns';
    protected static $defaultDescription = 'Check integration FBA Returns';

    public function __construct(CheckIntegrationFbaReturn $amzFbaReturn)
    {
        $this->amzFbaReturn = $amzFbaReturn;
        parent::__construct();
    }

    private $amzFbaReturn;


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->amzFbaReturn->checkIntegrationReturns();
        return Command::SUCCESS;
    }
}
