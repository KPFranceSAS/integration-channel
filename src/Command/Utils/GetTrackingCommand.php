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
            "GALV22/000521",
            "GALV22/000520",
            "GALV22/000519",
            "GALV22/000518",
            "GALV22/000517",
            "GALV22/000516",
            "GALV22/000515",
            "GALV22/000514",
            "GALV22/000513",
            "GALV22/000512",
        ];

        foreach ($test as $tes) {
            $tracking = $this->dhlGetTracking->getTrackingExternalWeb($tes);
            $output->writeln($tes);
            dump($tracking);
        }





        return Command::SUCCESS;
    }
}
