<?php

namespace App\Command\Channels\Shopify\Flashled;


use App\Channels\Shopify\Flashled\FlashledAccountingIntegration;
use DateInterval;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:flashled-payout', 'Intgertae Flashled payout')]
class FlashledPayoutCommand extends Command
{
    public function __construct(private readonly FlashledAccountingIntegration $flashledApi)
    {
        parent::__construct();
    }



    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dateMin = new DateTime();
        $dateMin->sub(new DateInterval('P7D'));
        $params = [
            'date_min' => $dateMin->format('Y-m-d'),
            'status' => "paid"
        ];
        $this->flashledApi->integrateAllSettlements($params);

        return Command::SUCCESS;
    }
}
