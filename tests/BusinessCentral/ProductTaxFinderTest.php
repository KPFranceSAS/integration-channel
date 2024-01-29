<?php

namespace App\Tests\BusinessCentral;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\BusinessCentral\Connector\GadgetIberiaConnector;
use App\BusinessCentral\ProductTaxFinder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProductTaxFinderTest extends KernelTestCase
{
    public function testCanonDigitalDefined(): void
    {
        $productTaxFinder = static::getContainer()->get(ProductTaxFinder::class);
        $bcConnector = static::getContainer()->get(GadgetIberiaConnector::class);
        $product = $bcConnector->getItemByNumber("X-MZB08KWEU");
        $canonDigital = $productTaxFinder->getCanonDigitalForItem(
            $product,
            BusinessCentralConnector::GADGET_IBERIA
        );
        $this->assertGreaterThan(0, $canonDigital, 'Canon digital defined');
    }


    public function testCanonDigitaNoDefined(): void
    {
        $productTaxFinder = static::getContainer()->get(ProductTaxFinder::class);
        $bcConnector = static::getContainer()->get(GadgetIberiaConnector::class);
        $product = $bcConnector->getItemByNumber("PX-P3D2044");
        $canonDigital = $productTaxFinder->getCanonDigitalForItem(
            $product,
            BusinessCentralConnector::GADGET_IBERIA
        );
        $this->assertEquals(0, $canonDigital, 'Canon digital not defined');
    }
}
