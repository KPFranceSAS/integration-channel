<?php

namespace App\Tests\Service\BusinessCentral;

use App\Helper\BusinessCentral\Connector\BusinessCentralConnector;
use App\Service\BusinessCentral\GadgetIberiaConnector;
use App\Service\BusinessCentral\ProductTaxFinder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProductTaxFinderTest extends KernelTestCase
{
    public function testCanonDigitalDefined(): void
    {
        $productTaxFinder = static::getContainer()->get(ProductTaxFinder::class);
        $bcConnector = static::getContainer()->get(GadgetIberiaConnector::class);
        $product = $bcConnector->getItemByNumber("X-MZB08KWEU");
        $canonDigital = $productTaxFinder->getCanonDigitalForItem(
            $product['id'],
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
            $product['id'],
            BusinessCentralConnector::GADGET_IBERIA
        );
        $this->assertEquals(0, $canonDigital, 'Canon digital not defined');
    }
}
