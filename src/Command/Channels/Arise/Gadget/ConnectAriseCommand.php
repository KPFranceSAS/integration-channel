<?php

namespace App\Command\Channels\Arise\Gadget;

use App\Channels\Arise\AriseApi;
use App\Channels\Arise\Gadget\GadgetApi;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConnectAriseCommand extends Command
{
    protected static $defaultName = 'app:arise-test';
    protected static $defaultDescription = 'Connection to Gadget express';

    public function __construct(GadgetApi $ariseApi, FilesystemOperator $ariseLabelStorage)
    {
        $this->ariseApi = $ariseApi;
        $this->ariseLabelStorage = $ariseLabelStorage;
        parent::__construct();
    }

    private $ariseApi;

    private $ariseLabelStorage;

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->printLabel();
        return Command::SUCCESS;
    }


    protected function printLabel(){
        $pdfLink = $this->ariseApi->getPrintLabel("FP0938511163");
        dump($pdfLink);
        $pdfContent =file_get_contents($pdfLink);
            
        $filename = "WPV21-01304_67031224042_".date('YmdHis').'.pdf';
        $this->ariseLabelStorage->write($filename, $pdfContent);
        $link = "https://marketplace.kps-group.com/labels/".$filename;
        dump($link);
    }
    private function getSeller()
    {
        $result = $this->ariseApi->getSeller();
        var_dump($result);
    }
    
    private function getBrandProduct()
    {
        $result = $this->ariseApi->getBrandProduct(1355778698378079);
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
        $result = $this->ariseApi->updatePrice(1355779509600516, 2068482878177540, "X-W2040OV4N", 0);
        var_dump($result);
    }


    private function updateStockLevel()
    {
        $result = $this->ariseApi->updateStockLevel(1355779509600516, 2068482878177540, "X-W2040OV4N", 2500);
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
