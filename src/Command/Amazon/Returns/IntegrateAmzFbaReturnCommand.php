<?php

namespace App\Command\Amazon\Returns;

use App\Service\Amazon\Returns\IntegrateAmzFbaReturn;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:amz-integrate-fba-returns', 'INtegrate FBA Returns')]
class IntegrateAmzFbaReturnCommand extends Command
{
    public function __construct(private readonly IntegrateAmzFbaReturn $amzFbaReturn)
    {
        parent::__construct();
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->amzFbaReturn->transformAllSaleReturns();
        return Command::SUCCESS;
    }
}
