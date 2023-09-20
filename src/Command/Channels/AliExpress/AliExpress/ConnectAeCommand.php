<?php

namespace App\Command\Channels\AliExpress\AliExpress;

use App\BusinessCentral\Connector\GadgetIberiaConnector;
use App\Channels\AliExpress\AliExpress\AliExpressApi;
use App\Channels\AliExpress\AliExpress\AliExpressIntegrateOrder;
use App\Channels\AliExpress\AliExpress\AliExpressStock;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConnectAeCommand extends Command
{
    protected static $defaultName = 'app:ae-test';
    protected static $defaultDescription = 'Connection to Ali express';

    public function __construct(AliExpressApi $aliExpress, AliExpressIntegrateOrder $aliExpressIntegrateOrder, GadgetIberiaConnector $gadgetIberiaConnector, AliExpressStock $aliExpressStock)
    {
        $this->aliExpress = $aliExpress;
        $this->aliExpressStock = $aliExpressStock;
        $this->aliExpressIntegrateOrder = $aliExpressIntegrateOrder;
        $this->gadgetIberiaConnector = $gadgetIberiaConnector;

        parent::__construct();
    }

    private $aliExpress;

    private $aliExpressStock;

    private $gadgetIberiaConnector;

    private $aliExpressIntegrateOrder;


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        dump($this->aliExpress->getOrder("3016506064585909"));
        return Command::SUCCESS;
    }



    private function updatePrice()
    {
        $result = $this->aliExpress->updatePrice("1005001794660227", "X-MUE4093GL", '34.99', '26.30');
        var_dump($result);
    }


    private function updateStockLevel()
    {
        $result = $this->aliExpress->updateStockLevel("1005001800940160", "X-PFJ4086EU", 1029);
        var_dump($result);
    }



    private function markCompanyTransport()
    {
        $order = $this->aliExpress->getOrder("3016506064585909");
        var_dump($order);
    }



    private function transformeOrder()
    {
        $order = $this->aliExpress->getOrder("3019315625910878");

        $transforme =  $this->aliExpressIntegrateOrder->transformToAnBcOrder($order);
        dump($transforme->transformToArray());
        $orderIntegrate = $this->gadgetIberiaConnector->createSaleOrder($transforme->transformToArray());
        $orderIntegrate = $this->gadgetIberiaConnector->getFullSaleOrder($orderIntegrate['id']);
        var_dump($orderIntegrate);
        var_dump($orderIntegrate['totalAmountIncludingTax']);
    }
}
