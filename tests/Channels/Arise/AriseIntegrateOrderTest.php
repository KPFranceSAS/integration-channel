<?php

namespace App\Tests\Channels\Arise;

use App\BusinessCentral\Connector\GadgetIberiaConnector;
use App\Channels\AliExpress\AliExpress\AliExpressIntegrateOrder;
use App\Channels\Arise\Gadget\GadgetIntegrator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AriseIntegrateOrderTest extends KernelTestCase
{
    public function testTransformationClassic(): void
    {
        $aliExpressIntegrator = static::getContainer()->get(GadgetIntegrator::class);
        $order = $aliExpressIntegrator->getApi()->getOrder('67031224042');
        $orderBc = $aliExpressIntegrator->transformToAnBcOrder($order);
        $orderBc->customerNumber = AliExpressIntegrateOrder::ALIEXPRESS_CUSTOMER_NUMBER;
        $bcConnector = $aliExpressIntegrator->getBusinessCentralConnector(GadgetIberiaConnector::GADGET_IBERIA);
        $orderFinal = $bcConnector->createSaleOrder($orderBc->transformToArray());
        $saleOrderBc = $bcConnector->getFullSaleOrder($orderFinal['id']);
    }
}
