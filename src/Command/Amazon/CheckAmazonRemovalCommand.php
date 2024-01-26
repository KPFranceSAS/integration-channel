<?php

namespace App\Command\Amazon;

use App\Service\Amazon\Returns\GenerateAmzFbaRemoval;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:amz-check-removal', 'Build and change status amz')]
class CheckAmazonRemovalCommand extends Command
{
    public function __construct(private readonly GenerateAmzFbaRemoval $generateAmzFbaRemoval)
    {
        parent::__construct();
    }



    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->generateAmzFbaRemoval->process();
        return Command::SUCCESS;
    }
}
