<?php

namespace App\Command\Pricing;

use App\BusinessCentral\EcotaxUpdate;
use App\BusinessCentral\MsrpPriceUpdate;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:update-ecotaxes-from-bc', 'Update prices from BC')]
class UpdateEcotaxCommand extends Command
{
    public function __construct(protected EcotaxUpdate $ecotaxUpdate)
    {
        parent::__construct();
    }





    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->ecotaxUpdate->updateAllEcotaxes();
        return Command::SUCCESS;
    }
}
