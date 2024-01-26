<?php

namespace App\Command\Amazon\Returns;

use App\Service\Amazon\Returns\AssociateAmzFbaReimbursementReturns;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:amz-associates-returns', 'Associate FBA Returns')]
class AssociateAmzFbaReimbursementCommand extends Command
{
    public function __construct(private readonly AssociateAmzFbaReimbursementReturns $amzFbaReturn)
    {
        parent::__construct();
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->amzFbaReturn->associateToFbaReturns();
        return Command::SUCCESS;
    }
}
