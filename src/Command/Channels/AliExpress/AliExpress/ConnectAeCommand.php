<?php

namespace App\Command\Channels\AliExpress\AliExpress;

use App\BusinessCentral\Connector\GadgetIberiaConnector;
use App\Channels\AliExpress\AliExpress\AliExpressApi;
use App\Channels\AliExpress\AliExpress\AliExpressIntegrateOrder;
use App\Channels\AliExpress\AliExpress\AliExpressStock;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:ae-test', 'Connection to Ali express')]
class ConnectAeCommand extends Command
{
    public function __construct(private readonly AliExpressApi $aliExpress, private readonly AliExpressIntegrateOrder $aliExpressIntegrateOrder, private readonly GadgetIberiaConnector $gadgetIberiaConnector, private readonly AliExpressStock $aliExpressStock)
    {
        parent::__construct();
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        dump($this->aliExpress->updatePrice(1_005_002_778_809_836, 'X-GDS4147GL', '12.99', '11.50'));
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
