<?php

namespace App\Command\Amazon\Returns;

use App\Service\Amazon\Returns\IntegrateAmzFbaReturn;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IntegrateAmzFbaReturnCommand extends Command
{
    protected static $defaultName = 'app:amz-integrate-fba-returns';
    protected static $defaultDescription = 'INtegrate FBA Returns';

    public function __construct(IntegrateAmzFbaReturn $amzFbaReturn)
    {
        $this->amzFbaReturn = $amzFbaReturn;
        parent::__construct();
    }

    private $amzFbaReturn;


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->amzFbaReturn->transformAllSaleReturns();
        return Command::SUCCESS;
    }
}
