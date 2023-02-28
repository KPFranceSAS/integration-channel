<?php

namespace App\Command\Channels\Mirakl\Decathlon;

use App\BusinessCentral\Connector\KpFranceConnector;
use App\Channels\Mirakl\Decathlon\DecathlonApi;
use App\Channels\Mirakl\Decathlon\DecathlonSyncProduct;
use Mirakl\MCI\Shop\Request\Hierarchy\GetHierarchiesRequest;
use Mirakl\MMP\Shop\Request\Channel\GetChannelsRequest;
use Mirakl\MMP\Shop\Request\Offer\GetAccountRequest;
use Mirakl\MMP\Shop\Request\Order\Get\GetOrdersRequest;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConnectDecathlonCommand extends Command
{
    protected static $defaultName = 'app:decathlon-test';
    protected static $defaultDescription = 'Connection to Deacthlon';

    public function __construct(
        DecathlonApi $decathlonApi,
        DecathlonSyncProduct $decathlonSyncProduct,
        KpFranceConnector $kpFranceConnector
    ) {
        $this->decathlonApi = $decathlonApi;
        $this->decathlonSyncProduct = $decathlonSyncProduct;
        $this->kpFranceConnector = $kpFranceConnector;
        parent::__construct();
    }

    private $decathlonApi;

    private $kpFranceConnector;
    

    private $decathlonSyncProduct;
  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $invoice = $this->kpFranceConnector->getSaleInvoiceByNumber('FVF23/0201376');
        $invoiceContent =  $this->kpFranceConnector->getContentInvoicePdf($invoice["id"]);
        dump($this->decathlonApi->sendInvoice('fr9534967739_f939eaa403024b19b9cacd4648a3735b-A', 'FVF23/0201376', $invoiceContent));
       
        return Command::SUCCESS;
    }


    protected function decathlonSyncProduct()
    {
    }



    protected function connectToDecathlon()
    {
        $api = $this->decathlonApi->getClient();
        $request = new GetAccountRequest();
        $result = $api->getAccount($request);
        dump($result);
    }


    protected function getChannels()
    {
        $api = $this->decathlonApi->getClient();
        $request = new GetChannelsRequest();
        $result = $api->getChannels($request);
        dump($result);
    }



    protected function getCategories()
    {
        $api = $this->decathlonApi->getClient();
        $request = new GetHierarchiesRequest();
        //$request->setMaxLevel(2);
        $request->setHierarchyCode('30058');
        $result = $api->getHierarchies($request);
        dump($result);
    }



    protected function getOrdersArray()
    {
        $result = $this->decathlonApi->getAllOrdersToSend();
       
        dump(json_encode($result));
    }



    protected function getOrders()
    {
        $api = $this->decathlonApi->getClient();
        $request = new GetOrdersRequest();
        
        $result = $api->getOrders($request);
        dump($result);
    }


    protected function getAllOrders()
    {
        $api = $this->decathlonApi->getClient();
        $request = new GetOrdersRequest();
        
        $result = $api->getOrders($request);
        dump($result);
    }
}
