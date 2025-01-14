<?php

namespace App\Command\Channels\Shopify\Minibatt;


use App\Channels\Shopify\Minibatt\MinibattAccountingIntegration;
use DateInterval;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:minibatt-payout', 'Intgertae Minibatt payout')]
class MinibattPayoutCommand extends Command
{
    public function __construct(private readonly MinibattAccountingIntegration $minibattAccountingIntegration)
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
        $this->minibattAccountingIntegration->integrateAllSettlements($params);

        return Command::SUCCESS;
    }
}
