<?php

namespace App\Command\Amazon;

use App\Service\Amazon\AmzFbaReturn;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IntegrateFbaReturnCommand extends Command
{
    protected static $defaultName = 'app:amz-generate-returns';
    protected static $defaultDescription = 'Generate FBA Returns';

    public function __construct(AmzFbaReturn $amzFbaReturn)
    {
        $this->amzFbaReturn = $amzFbaReturn;
        parent::__construct();
    }

    private $amzFbaReturn;


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->amzFbaReturn->generateReturns();
        return Command::SUCCESS;
    }
}
