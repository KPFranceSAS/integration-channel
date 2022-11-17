<?php

namespace App\Command\Channels\Shopify\Minibatt;

use App\Channels\Shopify\Minibatt\MinibattApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportMinibattShopifyTransactionCommand extends Command
{
    protected static $defaultName = 'app:export-minibatt-shopify';
    protected static $defaultDescription = 'Cretae export file of all shopify tracnsaction on';

    public function __construct(MinibattApi $minibattApi)
    {
        $this->minibattApi = $minibattApi;
        parent::__construct();
    }

    private $minibattApi;


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $orders = $this->minibattApi->getAllShopifyPaiements();
        $path = 'minibatt-paiements.json';
        $jsonString = json_encode($orders, JSON_PRETTY_PRINT);
        // Write in the file
        $fp = fopen($path, 'w');
        fwrite($fp, $jsonString);
        fclose($fp);

        return Command::SUCCESS;
    }
}
