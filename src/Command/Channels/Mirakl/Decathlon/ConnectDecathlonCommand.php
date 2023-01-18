<?php

namespace App\Command\Channels\Mirakl\Decathlon;

use App\Channels\Mirakl\Decathlon\DecathlonApi;
use Mirakl\MCI\Shop\Request\Attribute\GetAttributesRequest;
use Mirakl\MCI\Shop\Request\Hierarchy\GetHierarchiesRequest;
use Mirakl\MCI\Shop\Request\ValueList\GetValueListsItemsRequest;
use Mirakl\MMP\Common\Domain\Product\Offer\ProductReference;
use Mirakl\MMP\Shop\Request\Channel\GetChannelsRequest;
use Mirakl\MMP\Shop\Request\Offer\GetAccountRequest;
use Mirakl\MMP\Shop\Request\Order\Get\GetOrdersRequest;
use Mirakl\MMP\Shop\Request\Product\GetProductsRequest;
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
        $this->getChannels();
        
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
        $request->setLocale('fr_FR');
        $result = $api->getAccount($request);
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


    protected function getProductAttributes()
    {
        $api = $this->decathlonApi->getClient();
        $request = new GetAttributesRequest();
        $request->setMaxLevel(0);
        $request->setHierarchyCode('30061');
        $result = $api->getAttributes($request);
        dump($result);
    }


    protected function getProductAttributesValues()
    {
        $api = $this->decathlonApi->getClient();
        $request = new GetValueListsItemsRequest();
        $request->setData('hierarchy', '30061');
        $result = $api->getValueListsItems($request);

        dump($result);
    }


    protected function getOrders()
    {
        $api = $this->decathlonApi->getClient();
        $request = new GetOrdersRequest();
        
        $result = $api->getOrders($request);
        dump($result);
    }
}
