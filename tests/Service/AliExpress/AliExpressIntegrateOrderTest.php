<?php

namespace App\Tests\Service\AliExpress;

use App\Service\AliExpress\AliExpressIntegrateOrder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AliExpressIntegrateOrderTest extends KernelTestCase
{
    public function testSomething(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $aliExpressIntegrateOrder = static::getContainer()->get(AliExpressIntegrateOrder::class);
    }
}
