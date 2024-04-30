<?php

namespace App\Command\Utils;

use App\Service\Carriers\ShippyProTracking;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:shippy-pro', 'Shippy pro')]
class ShippyProCommand extends Command
{
    public function __construct(private readonly ShippyProTracking $shippyProTracking)
    {
        parent::__construct();
    }

    private $manager;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        dd($this->shippyProTracking->findTracking("ALV24/010274"));
      
        return Command::SUCCESS;
    }
}
