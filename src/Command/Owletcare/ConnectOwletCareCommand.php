<?php

namespace App\Command\Owletcare;

use App\Service\OwletCare\OwletCareApi;
use App\Service\OwletCare\OwletCareIntegrateOrder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConnectOwletCareCommand extends Command
{
    protected static $defaultName = 'app:owletcare-test';
    protected static $defaultDescription = 'Connection to owletcare test';

    public function __construct(OwletCareApi $owletCareApi, OwletCareIntegrateOrder $owletCareIntegrateOrder)
    {
        $this->owletCareApi = $owletCareApi;
        $this->owletCareIntegrateOrder = $owletCareIntegrateOrder;
        parent::__construct();
    }

    private $owletCareIntegrateOrder;

    private $owletCareApi;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $mainLocation = $this->owletCareApi->getMainLocation();

        $orders = $this->owletCareApi->getAllOrdersToSend();
        /*foreach ($orders as $order) {
            //dump($order);
            $ids = [];
            foreach ($order['line_items'] as $item) {
                $ids[] = ['id' => $item['id']];
            }

            $response = $this->owletCareApi->markAsFulfilled($order['id'], $mainLocation['id'], $ids);
            dump($response->getDecodedBody());
            return Command::SUCCESS;
        }*/






        return Command::SUCCESS;
    }
}
