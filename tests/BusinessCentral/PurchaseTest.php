<?php

namespace App\Tests\BusinessCentral;

use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PurchaseTest extends KernelTestCase
{
    public function testAllPurchaseOrders(): void
    {
        /** @var KitPersonalizacionSportConnector */
        $bcConnector = static::getContainer()->get(KitPersonalizacionSportConnector::class);
        $product = $bcConnector->getItemByNumber("PX-P3D2044");
        $purchaseOrders = $bcConnector->getPurchaseInvoicesByItemNumber($product['id']);
        $this->assertIsArray($purchaseOrders);
    }
}
