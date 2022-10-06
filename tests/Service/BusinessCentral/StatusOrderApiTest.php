<?php

namespace App\Tests\Service\BusinessCentral;

use App\BusinessCentral\Connector\GadgetIberiaConnector;
use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class StatusOrderApiTest extends KernelTestCase
{
    public function testGetStatus(): void
    {
        $kitConn = static::getContainer()->get(KitPersonalizacionSportConnector::class);
        $status = $kitConn->getStatusOrderByNumber('PV22/000761');
        $this->assertIsArray($status);
        $this->assertIsArray($status["statusOrderLines"]);
        $this->assertSame($status["statusOrderLines"][0]["statusCode"], "99");
    }


    public function testGetStatusInvoiced(): void
    {
        $kitConn = static::getContainer()->get(GadgetIberiaConnector::class);
        $status = $kitConn->getStatusOrderByNumber('WPV21-00187');
        $this->assertIsArray($status);
        $this->assertIsArray($status["statusOrderLines"]);
        $this->assertSame($status["statusOrderLines"][0]["statusCode"], "4");
    }
}
