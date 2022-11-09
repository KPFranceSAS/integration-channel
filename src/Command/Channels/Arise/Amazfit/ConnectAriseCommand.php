<?php

namespace App\Command\Channels\Arise\Amazfit;

use App\Channels\Arise\Amazfit\AmazfitApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConnectAriseCommand extends Command
{
    protected static $defaultName = 'app:amazfit-test';
    protected static $defaultDescription = 'Connection to Arise amazfit';

    public function __construct(AmazfitApi $ariseApi)
    {
        $this->ariseApi = $ariseApi;
        parent::__construct();
    }

    private $ariseApi;

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->getOrders();
        return Command::SUCCESS;
    }

    
    private function getBrandProduct()
    {
        $result = $this->ariseApi->getBrandProduct(1355781352423031);
        var_dump($result);
    }

    private function getSeller()
    {
        $result = $this->ariseApi->getSeller();
        var_dump($result);
    }

    private function getCarriers()
    {
        $result = $this->ariseApi->getDbsShipmentProviders();
        var_dump($result);
    }
    
    private function getSupplierByName()
    {
        $result = $this->ariseApi->getSupplierCode('DHL');
        var_dump($result);
    }
    
    


    private function updatePrice()
    {
        $result = $this->ariseApi->updatePrice(1355781352423031, 2068484754234999, "AMF-W2170OV6N", 239.9);
        var_dump($result);
    }


    private function updateStockLevel()
    {
        $result = $this->ariseApi->updateStockLevel(1355781352423031, 2068484754234999, "AMF-W2170OV6N", 2500);
        var_dump($result);
    }



    private function markOrderAsFulfill()
    {
        $result = $this->ariseApi->markOrderAsFulfill(46301516006, "DHL", "0837682350");
        var_dump($result);
    }


    private function getProducts()
    {
        $products = $this->ariseApi->getAllProducts();
        dump($products);
    }


    private function getOrders()
    {
        $orders = $this->ariseApi->getAllOrdersToSend();
        //dump($orders);

        foreach ($orders as $order) {
            $order =  $this->ariseApi->getOrder($order->order_id);
            dump($order);
        }
    }
}
