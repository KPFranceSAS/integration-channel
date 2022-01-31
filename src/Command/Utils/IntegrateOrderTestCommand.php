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
        BusinessCentralAggregator $bcAggregator,
        IntegratorAggregator $integratorAggregator,
        ChannelWebservice $channelWebservice
    ) {
        $this->bcConnector = $bcAggregator->getBusinessCentralConnector(BusinessCentralConnector::KP_FRANCE);
        $this->integrator = $integratorAggregator->getIntegrator(WebOrder::CHANNEL_CHANNELADVISOR);
        $this->channelWebservice = $channelWebservice;
        parent::__construct();
    }

    private $bcConnector;

    private $integrator;

    private $channelWebservice;


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        //$this->createOrderTest();


        $product = $this->bcConnector->getFullSaleInvoiceByNumber("FV22/0102646");
        dump($product);


        return Command::SUCCESS;
    }



    private function getOldInvoice()
    {
        $product = $this->bcConnector->getSaleInvoiceByNumber("FV20/0200127");
        dump($product);
    }




    private function createOrderTest()
    {
        $product = $this->bcConnector->getItemByNumber("PX-P3D2051");
        dump($product);
        $account = $this->bcConnector->getAccountByNumber("758000");
        dump($account);
        dump($this->bcConnector->getCustomerByNumber("000230"));


        $lines = [
            [
                "lineType" => "Item",
                "itemId" => $product["id"],
                "unitPrice" => 219.0,
                "quantity" => 2,
                'discountAmount' => 0
            ],

            [
                "lineType" => "Account",
                'accountId' => $account['id'],
                "unitPrice" => 5.99,
                "quantity" => 1,
                "description" => "Shipping fees",
            ],
        ];


        $order =  [
            'orderDate' => date("Y-m-d"),
            'customerNumber' => "000230",

            "billToName" => "Vipul Parmar",
            "sellingPostalAddress" => [
                "street" => "9 GATEHILL \r\nGARDENS",
                "postalCode" => "66840",
                "city" => "Bourg Madame",
                "countryLetterCode" => "FR",
            ],

            "shipToName" => "Vipul Parmar",
            "shippingPostalAddress" => [
                "street" => "9 GATEHILL \r\nGARDENS",
                "city" => "LUTON",
                "state" => "West Yorkshire",
                "postalCode" => "LU3 4EZ",
                "countryLetterCode" => "GB",
            ],
            'salesOrderLines' => $lines,
            'pricesIncludeTax' => true,
            "phoneNumber" => '0565458585',
            "email" => "wsv5fqfhhlm92wr@marketplace.amazon.co.uk",
            "externalDocumentNumber" => "XXXX-XXXX-XXXX",
        ];



        $order = $this->bcConnector->createSaleOrder($order);
        $orderFull = $this->bcConnector->getFullSaleOrder($order['id']);
        dump($orderFull);
    }
}
