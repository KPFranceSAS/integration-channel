<?php

namespace App\Tests\Service\BusinessCentral;


use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\Service\Flashled\FlashledIntegrateOrder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BundleTest extends KernelTestCase
{

    public function testIntegrationClassic(): void
    {
        $bcConnector = static::getContainer()->get(KitPersonalizacionSportConnector::class);
        $product = $bcConnector->getItemByNumber("FLS-PCK-MNSXL-FLSLED");
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
            'customerNumber' => FlashledIntegrateOrder::FLASHLED_CUSTOMER_NUMBER,
            "locationCode" => "LAROCA",
            'salesOrderLines' => $lines,
            'pricesIncludeTax' => true,
            "phoneNumber" => '0565458585',
            "externalDocumentNumber" => "Integration-" . date('YmdHis'),
            "shippingAgent" => "DHL PARCEL",
            "shippingAgentService" => "DHL1",
            "paymentMethodCode" => "PAYPAL"
        ];
        $order = $bcConnector->createSaleOrder($order);
        $this->assertIsArray($order);

        $orderFull = $bcConnector->getFullSaleOrder($order['id']);
        $this->assertIsArray($orderFull);
        $this->assertCount(1, $orderFull['salesOrderLines']);
    }


}
