<?php

namespace App\Tests\Service\Flashled;

use App\Service\BusinessCentral\KitPersonalizacionSportConnector;
use App\Service\Flashled\FlashledIntegrateOrder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FlashledConnectorTest extends KernelTestCase
{
    public function testIntegrationClassic(): void
    {
        $bcConnector = static::getContainer()->get(KitPersonalizacionSportConnector::class);
        $product = $bcConnector->getItemByNumber("WK-FLASHLED-S");
        $lines = [
            [
                "lineType" => "Item",
                "itemId" => $product["id"],
                "unitPrice" => 50.0,
                "quantity" => 2,
                'discountAmount' => 0
            ],
        ];
        $order =  [
            'orderDate' => date("Y-m-d"),
            'customerNumber' => FlashledIntegrateOrder::FLASHLED_CUSTOMER_NUMBER,

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
            "externalDocumentNumber" => "FLASHLED-" . date('YmdHis'),
            "shippingAgent" => "DHL PARCEL",
            "shippingAgentService" => "DHL1"
        ];
        $order = $bcConnector->createSaleOrder($order);
        $this->assertIsArray($order);

        $orderFull = $bcConnector->getFullSaleOrder($order['id']);
        $this->assertIsArray($orderFull);
        $this->assertCount(1, $orderFull['salesOrderLines']);
        $this->assertSame($orderFull['shippingAgent'], "DHL PARCEL");
        $this->assertSame($orderFull['shippingAgentService'], "DHL1");
    }



    public function testIntegrationBundle(): void
    {
        $bcConnector = static::getContainer()->get(KitPersonalizacionSportConnector::class);
        $product = $bcConnector->getItemByNumber("FLS-PCK-MNSXL-FLSLED");
        $lines = [
            [
                "lineType" => "Item",
                "itemId" => $product["id"],
                "unitPrice" => 50.0,
                "quantity" => 2,
                'discountAmount' => 0
            ],
        ];
        $order =  [
            'orderDate' => date("Y-m-d"),
            'customerNumber' => FlashledIntegrateOrder::FLASHLED_CUSTOMER_NUMBER,
            "locationCode" => "LAROCA",
            'salesOrderLines' => $lines,
            'pricesIncludeTax' => true,
            "externalDocumentNumber" => "FLASHLED-BUDNLE-" . date('YmdHis'),
            "shippingAgent" => "DHL PARCEL",
            "shippingAgentService" => "DHL1"
        ];

        $order = $bcConnector->createSaleOrder($order);
        $this->assertIsArray($order);

        $orderFull = $bcConnector->getFullSaleOrder($order['id']);
        $this->assertIsArray($orderFull);
        $this->assertCount(1, $orderFull['salesOrderLines']);
        $this->assertSame($orderFull['shippingAgent'], "DHL PARCEL");
        $this->assertSame($orderFull['shippingAgentService'], "DHL1");
    }
}
