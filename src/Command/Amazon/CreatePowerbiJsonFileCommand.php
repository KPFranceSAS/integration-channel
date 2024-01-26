<?php

namespace App\Command\Amazon;

use App\Service\Amazon\Report\PublishPowerBi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


#[\Symfony\Component\Console\Attribute\AsCommand('app:amz-create-json-files', 'Export json files for Power BI')]
class CreatePowerbiJsonFileCommand extends Command
{
    public function __construct(private readonly PublishPowerBi $publishPowerBi)
    {
        parent::__construct();
    }



    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->publishPowerBi->exportAll();
        return Command::SUCCESS;
    }
}
