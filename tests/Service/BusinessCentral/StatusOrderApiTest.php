<?php

namespace App\Tests\Service\BusinessCentral;

use App\Service\BusinessCentral\KitPersonalizacionSportConnector;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class StatusOrderApiTest extends KernelTestCase
{
    public function testGet(): void
    {
        $kitConn = static::getContainer()->get(KitPersonalizacionSportConnector::class);
        $status = $kitConn->getStatusOrderByNumber('PV22/000761');
        $this->assertIsArray($status);
        $this->assertIsArray($status["statusOrderLines"]);
    }
}
