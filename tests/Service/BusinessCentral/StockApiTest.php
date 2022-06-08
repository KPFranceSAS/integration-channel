<?php

namespace App\Tests\Service\BusinessCentral;

use App\Service\BusinessCentral\KitPersonalizacionSportConnector;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class StockApiTest extends KernelTestCase
{
    public function testGetItemStock(): void
    {
        $kitConn = static::getContainer()->get(KitPersonalizacionSportConnector::class);
        dump($kitConn->getStockPerProduct('PX-P3D2576'));
    }


    public function testGetItemStockLocation(): void
    {
        $kitConn = static::getContainer()->get(KitPersonalizacionSportConnector::class);
        dump($kitConn->getStockPerProductPerLocation('PX-P3D2576', 'AMAZON'));
    }

    public function testGetCustomer(): void
    {
        $kitConn = static::getContainer()->get(KitPersonalizacionSportConnector::class);
        dump($kitConn->getCustomerByNumber('121051'));
    }
}
