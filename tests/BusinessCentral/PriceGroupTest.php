<?php

namespace App\Tests\BusinessCentral;

use App\BusinessCentral\Connector\GadgetIberiaConnector;
use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\BusinessCentral\Connector\KpFranceConnector;
use App\Channels\AliExpress\AliExpress\AliExpressIntegrateOrder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PriceGroupTest extends KernelTestCase
{
    public function testIntegrationClassic(): void
    {
        $bcConnector = static::getContainer()->get(KpFranceConnector::class);
        $product = $bcConnector->getItemByNumber("0130-RS");
        $lines = [
            [
                "lineType" => "Item",
                "itemId" => $product["id"],
                "quantity" => 2,
            ],
        ];
        $order =  [
            'orderDate' => date("Y-m-d"),
            'customerNumber' => "000013",
            "locationCode" => "LAROCA",
            'salesOrderLines' => $lines,
        ];
        $order = $bcConnector->createSaleOrder($order);
        $this->assertIsArray($order);

        $orderFull = $bcConnector->getFullSaleOrder($order['id']);
    }
}
