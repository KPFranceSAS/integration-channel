<?php

namespace App\Command\Utils;



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
            "externalDocumentNumber" => "205-4086795-2477136",
        ];



        $order = $this->bcConnector->createSaleOrder($order);

        dump($order);


        return Command::SUCCESS;
    }
}
