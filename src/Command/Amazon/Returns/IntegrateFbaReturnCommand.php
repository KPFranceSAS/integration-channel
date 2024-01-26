<?php

namespace App\Command\Amazon\Returns;

use App\Service\Amazon\Returns\GenerateAmzFbaReturn;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:amz-generate-returns', 'Generate FBA Returns')]
class IntegrateFbaReturnCommand extends Command
{
    public function __construct(private readonly GenerateAmzFbaReturn $amzFbaReturn)
    {
        parent::__construct();
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->amzFbaReturn->generateReturns();
        return Command::SUCCESS;
    }
}
