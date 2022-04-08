<?php

namespace App\Command\Utils;

use App\Entity\WebOrder;
use App\Helper\BusinessCentral\Connector\BusinessCentralConnector;
use App\Service\BusinessCentral\BusinessCentralAggregator;
use App\Service\ChannelAdvisor\ChannelWebservice;
use App\Service\Integrator\IntegratorAggregator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IntegrateOrderTestCommand extends Command
{
    protected static $defaultName = 'app:integrate-order-test';
    protected static $defaultDescription = 'integrate order test';

    public function __construct(
        BusinessCentralAggregator $bcAggregator
    ) {
        $this->bcConnector = $bcAggregator->getBusinessCentralConnector(BusinessCentralConnector::KIT_PERSONALIZACION_SPORT);
        parent::__construct();
    }

    private $bcConnector;

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {


        $this->createOrderTest();
        return Command::SUCCESS;
    }



    private function getOldInvoice()
    {
        $product = $this->bcConnector->getFullSaleOrderByNumber("FV20/0200127");
        dump($product);
    }




    private function createOrderTest()
    {
        $product = $this->bcConnector->getItemByNumber("X-MZB08KWEU");

        $lines = [
            [
                "lineType" => "Item",
                "itemId" => $product["id"],
                "unitPrice" => 219.0,
                "quantity" => 2,
                'discountAmount' => 0
            ],
        ];


        $shipments = $this->bcConnector->getShipmentMethodByCode('AIR');
        dump($shipments);

        $order =  [
            'orderDate' => date("Y-m-d"),
            'customerNumber' => "008452",
            "locationCode" => "CENTRAL",
            'salesOrderLines' => $lines,
            'pricesIncludeTax' => true,
            "shippingAgent" => 'DHL PARCEL',
            "shippingAgentService" => 'AEREO',
            "phoneNumber" => '0565458585',
            "email" => "wsv5fqfhhlm92wr@marketplace.amazon.co.uk",
            "externalDocumentNumber" => "length-XXXX-XXXX-XXXX",
        ];



        $order = $this->bcConnector->createSaleOrder($order);
        $orderFull = $this->bcConnector->getFullSaleOrder($order['id']);
        dump($orderFull);
    }
}
