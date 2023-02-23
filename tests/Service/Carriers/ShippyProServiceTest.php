<?php

namespace App\Tests\Service\Carriers;

use App\Service\Carriers\ShippyProTracking;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ShippyProServiceTest extends KernelTestCase
{
    public function testIntegrationClassic(): void
    {
        $shippyPro = static::getContainer()->get(ShippyProTracking::class);
        dump($shippyPro->getTracking('1Z8F56Y16890814419'));
    }
}
