<?php

namespace App\Command\Utils;

use App\Entity\WebOrder;
use App\Helper\BusinessCentral\Connector\BusinessCentralConnector;
use App\Service\AliExpress\AliExpressIntegrateOrder;
use App\Service\BusinessCentral\BusinessCentralAggregator;
use App\Service\ChannelAdvisor\ChannelWebservice;
use App\Service\Integrator\IntegratorAggregator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IntegrateOrderCommand extends Command
{
    protected static $defaultName = 'app:integrate-order-test';
    protected static $defaultDescription = 'integrate order test';

    public function __construct(
        BusinessCentralAggregator $bcAggregator
    ) {
        $this->bcConnector = $bcAggregator->getBusinessCentralConnector(BusinessCentralConnector::KP_FRANCE);
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
        //$product = $this->bcConnector->getItemByNumber("X-MZB08KWEU");

        $product = $this->bcConnector->getItemByNumber("PX-P3D2044");

        dump($product);
        return;
        $lines = [
            [
                "lineType" => "Item",
                "itemId" => $product["id"],
                "unitPrice" => 219.0,
                "quantity" => 2,
                'discountAmount' => 0
            ],
        ];



        $order =  [
            'orderDate' => date("Y-m-d"),
            'customerNumber' => AliExpressIntegrateOrder::ALIEXPRESS_CUSTOMER_NUMBER,

            "billToName" => "Vipul Parmar",
            "sellingPostalAddress" => [
                "street" => "Puerta K, Altea Hills Grupo 3, Residencia los Olivos",
                "postalCode" => "66840",
                "city" => "Calle Berlin",
                "countryLetterCode" => "FR",
            ],
            "locationCode" => "AMAZON",
            "shipToName" => "Vipul Parmar",
            "shippingPostalAddress" => [
                "street" => "Puerta K, Altea Hills Grupo 3, Residencia los Olivos",
                "postalCode" => "66840",
                "city" => "Calle Berlin",
                "countryLetterCode" => "ES",
            ],
            'salesOrderLines' => $lines,
            'pricesIncludeTax' => true,
            "phoneNumber" => '0565458585',
            "email" => "wsv5fqfhhlm92wr@marketplace.amazon.co.uk",
            "externalDocumentNumber" => "tets-" . date('YmdHis'),
        ];




        $order = $this->bcConnector->createSaleOrder($order);
        $orderFull = $this->bcConnector->getFullSaleOrder($order['id']);
        dump($orderFull);
    }
}
