<?php

namespace App\Command\Lazada;

use App\Service\Lazada\LazadaApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConnectLazadaCommand extends Command
{
    protected static $defaultName = 'app:lazada-test';
    protected static $defaultDescription = 'Connection to Lazada express';

    public function __construct(LazadaApi $lazadaApi)
    {
        $this->lazadaApi = $lazadaApi;
        parent::__construct();
    }

    private $lazadaApi;

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->getProducts();
        return Command::SUCCESS;
    }



    private function updatePrice()
    {
        $result = $this->lazadaApi->updatePrice("1005001794660227", "X-MUE4093GL", '34.99', '26.30');
        var_dump($result);
    }


    private function updateStockLevel()
    {
        $result = $this->lazadaApi->updateStockLevel("1005001800940160", "X-PFJ4086EU", 1029);
        var_dump($result);
    }



    private function markCompanyTransport()
    {
        $order = $this->lazadaApi->getOrder("3016506064585909");
        var_dump($order);

        /*$carriers = $this->aliExpress->getCarriers();
        foreach ($carriers as $carrier) {
            var_dump($carrier->service_name);
        }
        */



        $result = $this->lazadaApi->markOrderAsFulfill("3015988148626826", "SPAIN_LOCAL_DHL", "0837590170");
        var_dump($result);
    }


    private function getProducts()
    {
        $products = $this->lazadaApi->getAllProducts();
        dump($products);
    }


    private function getOrders()
    {
        $orders = $this->lazadaApi->getAllOrdersToSend();
        dump($orders);

        $orders = $this->lazadaApi->getAllOrders();
        dump($orders);
    }
}
