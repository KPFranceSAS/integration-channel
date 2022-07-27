<?php

namespace App\Command\Amazon\Returns;

use App\Service\Amazon\Returns\UpdateAmzFbaReturn;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateFbaReturnCommand extends Command
{
    protected static $defaultName = 'app:amz-update-returns';
    protected static $defaultDescription = 'Update FBA Returns';

    public function __construct(UpdateAmzFbaReturn $amzFbaReturn)
    {
        $this->amzFbaReturn = $amzFbaReturn;
        parent::__construct();
    }

    private $amzFbaReturn;


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->amzFbaReturn->updateReturns();
        return Command::SUCCESS;
    }
}
