<?php

namespace App\Command\AliExpress;

use App\Service\AliExpress\AliExpressApi;
use App\Service\AliExpress\AliExpressIntegrateOrder;
use App\Service\BusinessCentral\GadgetIberiaConnector;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConnectAeCommand extends Command
{
    protected static $defaultName = 'app:ae-test';
    protected static $defaultDescription = 'Connection to Ali express';

    public function __construct(AliExpressApi $aliExpress, AliExpressIntegrateOrder $aliExpressIntegrateOrder, GadgetIberiaConnector $gadgetIberiaConnector)
    {
        $this->aliExpress = $aliExpress;
        $this->aliExpressIntegrateOrder = $aliExpressIntegrateOrder;
        $this->gadgetIberiaConnector = $gadgetIberiaConnector;
        parent::__construct();
    }

    private $aliExpress;

    private $gadgetIberiaConnector;

    private $aliExpressIntegrateOrder;


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $order = $this->aliExpress->getProductInfo("1005001800841932");
        var_dump($order);


        return Command::SUCCESS;
    }



    private function updateStockLevel()
    {
        $result = $this->aliExpress->updateStockLevel("1005001800940160", "X-PFJ4086EU", 1029);
        var_dump($result);
    }



    private function markCompanyTransport()
    {
        $order = $this->aliExpress->getOrder("3015645808691774");
        var_dump($order);

        /*$carriers = $this->aliExpress->getCarriers();
        foreach ($carriers as $carrier) {
            var_dump($carrier->service_name);
        }
        */



        $result = $this->aliExpress->markOrderAsFulfill("3015403747487139", "SPAIN_LOCAL_DHL", "0837572830");
        var_dump($result);
    }




    private function transformeOrder()
    {

        $order = $this->aliExpress->getOrder("8143448047401326");

        $transforme =  $this->aliExpressIntegrateOrder->transformToAnBcOrder($order);

        $orderIntegrate = $this->gadgetIberiaConnector->createSaleOrder($transforme->transformToArray());
        $orderIntegrate = $this->gadgetIberiaConnector->getFullSaleOrder($orderIntegrate['id']);
        var_dump($orderIntegrate);
        var_dump($orderIntegrate['totalAmountIncludingTax']);
    }
}
