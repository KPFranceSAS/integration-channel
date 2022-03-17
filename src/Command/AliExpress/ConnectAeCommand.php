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
        // $result = $this->aliExpress->markOrderAsFulfill("3015988148626826", "SPAIN_LOCAL_DHL", "0837590170");

        // $result = $this->aliExpress->getOrder("3016042381461820");
        $result  = $this->gadgetIberiaConnector->getSaleInvoiceByOrderNumber("3016049952530321", "002355");
        $result  = $this->gadgetIberiaConnector->getSaleInvoiceByExternalDocumentNumberCustomer("3016049952530321", "002355");
        dump($result);


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



        $result = $this->aliExpress->markOrderAsFulfill("3015988148626826", "SPAIN_LOCAL_DHL", "0837590170");
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
