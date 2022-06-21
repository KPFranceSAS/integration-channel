<?php

namespace App\Tests\Service\BusinessCentral;

use App\Service\BusinessCentral\KitPersonalizacionSportConnector;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PurchaseTest extends KernelTestCase
{
    public function testAllPurchaseOrders(): void
    {
        /** @var KitPersonalizacionSportConnector */
        $bcConnector = static::getContainer()->get(KitPersonalizacionSportConnector::class);
        $product = $bcConnector->getItemByNumber("PX-P3D2044");
        $purchaseOrders = $bcConnector->getPurchaseInvoicesByItemNumber($product['id']);
        dump(count($purchaseOrders));
    }
}
