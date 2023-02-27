<?php

namespace App\Command\Channels\Mirakl\Decathlon;

use App\Channels\Mirakl\Decathlon\DecathlonApi;
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

    public function __construct(DecathlonApi $decathlonApi)
    {
        $this->decathlonApi = $decathlonApi;
        parent::__construct();
    }

    private $decathlonApi;


  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->getOrdersArray();
       
        return Command::SUCCESS;
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
