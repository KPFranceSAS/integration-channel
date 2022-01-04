<?php

namespace App\Command;



use App\Service\BusinessCentral\BusinessCentralConnector;
use App\Service\ChannelAdvisor\ChannelWebservice;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IntegrateOrderTestCommand extends Command
{
    protected static $defaultName = 'app:integrate-order-test';
    protected static $defaultDescription = 'integrate order test';

    public function __construct(BusinessCentralConnector $saleOrderConnector, ChannelWebservice $channelWebservice)
    {
        $this->bcConnector = $saleOrderConnector;
        $this->channelWebservice = $channelWebservice;
        parent::__construct();
    }

    /**
     *
     * @var BusinessCentralConnector
     */
    private $bcConnector;


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $invoice = $this->bcConnector->getFullSaleOrderByNumber('WPV21-25319');
        dump($invoice);
        return 1;

        return Command::SUCCESS;


        $order = $this->channelWebservice->getAllDocumentsOrder(6894452);
        dump($order);

        return 1;

        $orderFinal = $this->bcConnector->getFullSaleOrder('62b60658-f4df-4d11-907d-7219682e2254');

        //$orderFinal = $this->bcConnector->getFullSaleOrder('62b60658-f4df-4d11-907d-7219682e2254');
        $invoice = $this->bcConnector->getSaleInvoiceByOrderNumber('WPV21-24168');
        dump($invoice);
        return 1;

        //$pdf = $this->bcConnector->getContentInvoicePdf($invoice['id']);

        $product = $this->bcConnector->getItemByNumber("PX-P3D2051");
        $account = $this->bcConnector->getAccountByNumber("758000");

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
                'lineDetails' => [
                    "number" => "758000"
                ]
            ],
        ];


        $order =  [
            'orderDate' => date("Y-m-d"),
            'customerNumber' => "000230",
            "currencyCode" => "GBP",
            "billToName" => "Vipul Parmar",
            "sellingPostalAddress" => [
                "street" => "9 GATEHILL rnGARDENS",
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
            "externalDocumentNumber" => "205-4086795-2477136",
        ];



        $order = $this->bcConnector->createSaleOrder($order);

        dump($order);


        return Command::SUCCESS;
    }
}
