<?php

namespace App\Tests\Service\Carriers;

use App\Service\Carriers\ShippyProTracking;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ShippyProServiceTest extends KernelTestCase
{
    public function testIntegrationClassic(): void
    {
        $shippyPro = static::getContainer()->get(ShippyProTracking::class);
        $tracking = $shippyPro->getTracking('1Z8F56Y16890814419');
        $this->assertNotNull($tracking);
    }
}
