<?php

namespace App\Command\Amazon;

use App\Service\Amazon\PublishPowerBi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class CreateOrdersJsonFileCommand extends Command
{
    protected static $defaultName = 'app:amz-create-json-files';
    protected static $defaultDescription = 'Export json files';

    public function __construct(PublishPowerBi $publishPowerBi)
    {
        $this->publishPowerBi = $publishPowerBi;
        parent::__construct();
    }

    private $publishPowerBi;



    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->publishPowerBi->exportAll();
        return Command::SUCCESS;
    }
}
