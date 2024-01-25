<?php

namespace App\Command\Amazon\Returns;

use App\Service\Amazon\Returns\AssociateAmzFbaReimbursementReturns;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AssociateAmzFbaReimbursementCommand extends Command
{
    protected static $defaultName = 'app:amz-associates-returns';
    protected static $defaultDescription = 'Associate FBA Returns';

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
