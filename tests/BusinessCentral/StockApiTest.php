<?php

namespace App\Tests\BusinessCentral;

use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\BusinessCentral\ProductStockFinder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class StockApiTest extends KernelTestCase
{
    public function testGetItemStock(): void
    {
        $kitConn = static::getContainer()->get(KitPersonalizacionSportConnector::class);
        $result = $kitConn->getStockAvailabilityPerProduct('PX-P3D2449');
        $this->assertIsArray($result);
        $this->assertEquals(0, $result['inventoryMADRID']);
        $this->assertEquals('PX-P3D2449', $result["no"]);

        $result = $kitConn->getStockAvailabilityPerProduct('PX-PD29');
        $this->assertNull($result);
    }



    public function testServiceItemStock(): void
    {
        $kitConn = static::getContainer()->get(ProductStockFinder::class);
        $result = $kitConn->getRealStockProductWarehouse('PX-P3D2449');
        $this->assertIsInt($result);

        $result2 = $kitConn->getRealStockProductWarehouse('PX-P3D2449');
        $this->assertIsInt($result2);
        $this->assertEquals($result2, $result);

        $result = $kitConn->getRealStockProductWarehouse('PX-PD29');
        $this->assertEquals(0, $result);
    }
}
