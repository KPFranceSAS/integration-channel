<?php

namespace App\Tests\BusinessCentral;

use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\BusinessCentral\ProductStockFinder;
use App\Entity\WebOrder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class StockBundleTest extends KernelTestCase
{
    public function testServiceItemStock(): void
    {
        $kitConn = static::getContainer()->get(ProductStockFinder::class);
        $result = $kitConn->getFinalStockProductWarehouse('PX-P3D2449');
        $result2 = $kitConn->getRealStockProductWarehouse('PX-P3D2449', WebOrder::DEPOT_LAROCA);
        $this->assertIsInt($result);
        $this->assertIsInt($result2);
        $this->assertNotEquals($result, $result2);


        $result3 = $kitConn->getFinalStockProductWarehouse('ANK-PCK-4');
        $this->assertIsInt($result3);
        dump($result3);
    }
}
