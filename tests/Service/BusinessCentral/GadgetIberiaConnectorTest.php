<?php

namespace App\Tests\Service\BusinessCentral;

use App\Service\AliExpress\AliExpressIntegrateOrder;
use App\Service\BusinessCentral\GadgetIberiaConnector;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GadgetIberiaConnectorTest extends KernelTestCase
{
    public function testIntegrationClassic(): void
    {
        $bcConnector = static::getContainer()->get(GadgetIberiaConnector::class);
        $product = $bcConnector->getItemByNumber("PX-P3D2044");
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
            "externalDocumentNumber" => "Integration-" . date('YmdHis'),
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


    public function testIntegrationBundleClassic(): void
    {
        $bcConnector = static::getContainer()->get(GadgetIberiaConnector::class);
        $product = $bcConnector->getItemByNumber("SNS-PCK-HC1-B");
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
            "locationCode" => "AMAZON",
            'salesOrderLines' => $lines,
            'pricesIncludeTax' => true,
            "email" => "wsv5fqfhhlm92wr@marketplace.amazon.co.uk",
            "externalDocumentNumber" => "testbundle-" . date('YmdHis'),
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


    public function testIntegrationCanonDigital(): void
    {
        $bcConnector = static::getContainer()->get(GadgetIberiaConnector::class);
        $product = $bcConnector->getItemByNumber("X-MZB08KWEU");
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
                "countryLetterCode" => "ES",
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
            "externalDocumentNumber" => "canondigital-" . date('YmdHis'),
            "shippingAgent" => "FBA",
            "shippingAgentService" => "1"
        ];
        $order = $bcConnector->createSaleOrder($order);
        $this->assertIsArray($order);
        $orderFull = $bcConnector->getFullSaleOrder($order['id']);
        $this->assertIsArray($orderFull);
        $this->assertCount(2, $orderFull['salesOrderLines']);
        $this->assertSame($orderFull['shippingAgent'], "FBA");
        $this->assertSame($orderFull['shippingAgentService'], "1");
    }


    public function testIntegrationNoCanonDigital(): void
    {
        $bcConnector = static::getContainer()->get(GadgetIberiaConnector::class);
        $product = $product = $bcConnector->getItemByNumber("X-MZB08KWEU");
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
            "externalDocumentNumber" => "nocanon-" . date('YmdHis'),
            "shippingAgent" => "DHL PARCEL",
            "shippingAgentService" => "DHL1"
        ];
        $order = $bcConnector->createSaleOrder($order);
        $this->assertIsArray($order);
        $orderFull = $bcConnector->getFullSaleOrder($order['id']);
        $this->assertIsArray($orderFull);
        $this->assertCount(1, $orderFull['salesOrderLines']);
    }





    public function testIntegrationCanaria(): void
    {
        $bcConnector = static::getContainer()->get(GadgetIberiaConnector::class);
        $product = $bcConnector->getItemByNumber("PX-P3D2044");
        $lines = [
            [
                "lineType" => "Item",
                "itemId" => $product["id"],
                "unitPrice" => 219.0,
                "quantity" => 2,
                'discountAmount' => 0
            ],
        ];
        $orderArray =  [
            'orderDate' => date("Y-m-d"),
            'customerNumber' => AliExpressIntegrateOrder::ALIEXPRESS_CUSTOMER_NUMBER,
            'salesOrderLines' => $lines,
            'pricesIncludeTax' => true,
            "phoneNumber" => '0565458585',
            "email" => "wsv5fqfhhlm92wr@marketplace.amazon.co.uk",
            "externalDocumentNumber" => "canaria-" . date('YmdHis'),
            "shippingAgent" => "DHL PARCEL",
            "shippingAgentService" => "DHL1"
        ];
        $order = $bcConnector->createSaleOrder($orderArray);
        $this->assertIsArray($order);

        $orderFull = $bcConnector->getFullSaleOrder($order['id']);
        $this->assertIsArray($orderFull);
        $this->assertCount(1, $orderFull['salesOrderLines']);


        $orderArrayCanarias = $orderArray;
        $orderArrayCanarias['customerNumber'] =  '002457';
        $orderCanarias = $bcConnector->createSaleOrder($orderArrayCanarias);
        $this->assertIsArray($orderCanarias);

        $orderFullCanarias = $bcConnector->getFullSaleOrder($orderCanarias['id']);
        $this->assertIsArray($orderFullCanarias);
        $this->assertCount(1, $orderFullCanarias['salesOrderLines']);

        $this->assertNotEquals($orderFullCanarias['totalTaxAmount'], $orderFull['totalTaxAmount']);
    }
}
