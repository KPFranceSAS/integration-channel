<?php

namespace App\Command\Channels\Shopify\Minibatt;

use App\Channels\Shopify\Minibatt\MinibattApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:export-minibatt', 'Connection to owletcare test')]
class ExportMinibattTransactionCommand extends Command
{
    public function __construct(private readonly MinibattApi $minibattApi)
    {
        parent::__construct();
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $final= [];
        $orders = $this->minibattApi->getAllOrders();
        foreach ($orders as $order) {
            $transactions = $this->minibattApi->getAllTransactions($order['id']);
            $final = array_merge($final, $transactions);
        }
        $path = 'minibatt.json';
        $jsonString = json_encode($orders, JSON_PRETTY_PRINT);
        // Write in the file
        $fp = fopen($path, 'w');
        fwrite($fp, $jsonString);
        fclose($fp);

        return Command::SUCCESS;
    }
}
