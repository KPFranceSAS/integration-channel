<?php

namespace App\Tests\Service\BusinessCentral;

use App\Service\BusinessCentral\KitPersonalizacionSportConnector;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class StatusOrderApiTest extends KernelTestCase
{
    public function testGet(): void
    {
        $kitConn = static::getContainer()->get(KitPersonalizacionSportConnector::class);
        dump($kitConn->getStatusOrderByNumber('	PV22/000761	'));
    }
}
