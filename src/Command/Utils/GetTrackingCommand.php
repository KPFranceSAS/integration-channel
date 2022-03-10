<?php

namespace App\Command\Utils;

use App\Service\Carriers\DhlGetTracking;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetTrackingCommand extends Command
{
    protected static $defaultName = 'app:get-tracking-dhl';
    protected static $defaultDescription = 'Get all trackings';

    public function __construct(
        DhlGetTracking $dhlGetTracking
    ) {
        $this->dhlGetTracking = $dhlGetTracking;
        parent::__construct();
    }


    private $dhlGetTracking;



    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $test = [
            "GALV22/000369",
            "GALV22/000367",
            "GALV22/000366",
            "GALV22/000365",
            "GALV22/000364",
        ];

        foreach ($test as $tes) {
            $tracking = $this->dhlGetTracking->getTrackingExternal($tes);
            $output->writeln($tes);
            dump($tracking);
        }





        return Command::SUCCESS;
    }
}
