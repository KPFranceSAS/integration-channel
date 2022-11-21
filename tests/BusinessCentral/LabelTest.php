<?php

namespace App\Tests\BusinessCentral;

use App\BusinessCentral\Connector\GadgetIberiaConnector;
use App\Channels\AliExpress\AliExpress\AliExpressIntegrateOrder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class LabelTest extends KernelTestCase
{
    public function testIntegrationClassic(): void
    {
        $bcConnector = static::getContainer()->get(GadgetIberiaConnector::class);
        $product = $bcConnector->getItemByNumber("PX-P3D2449");
        $lines = [
            [
                "lineType" => "Item",
                "itemId" => $product["id"],
                "unitPrice" => 269.95,
                "quantity" => 2,
                'discountAmount' => 0
            ],
        ];
        $order =  [
            'orderDate' => date("Y-m-d"),
            'customerNumber' => AliExpressIntegrateOrder::ALIEXPRESS_CUSTOMER_NUMBER,
            "locationCode" => "LAROCA",
            'salesOrderLines' => $lines,
            'pricesIncludeTax' => true,
            "phoneNumber" => '0565458585',
            "externalDocumentNumber" => "Arise-" . date('YmdHis'),
            "shippingAgent" => "ARISE",
            "shippingAgentService" => "Standard",
            "paymentMethodCode" => "PAYPAL",
            'URLEtiqueta' => 'https://marketplace.kps-group.com/pdf/Seller_Center.pdf'
        ];
        $order = $bcConnector->createSaleOrder($order);
        $this->assertIsArray($order);

        $orderFull = $bcConnector->getFullSaleOrder($order['id']);
        $this->assertIsArray($orderFull);
        $this->assertCount(1, $orderFull['salesOrderLines']);
        $this->assertEquals($orderFull['URLEtiqueta'], 'https://marketplace.kps-group.com/pdf/Seller_Center.pdf');
    }
}
