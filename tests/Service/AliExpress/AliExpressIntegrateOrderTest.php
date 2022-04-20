<?php

namespace App\Tests\Service\AliExpress;

use App\Entity\WebOrder;
use App\Service\AliExpress\AliExpressIntegrateOrder;
use App\Service\BusinessCentral\GadgetIberiaConnector;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AliExpressIntegrateOrderTest extends KernelTestCase
{
    public function testTransformationClassic(): void
    {
        $aliExpressIntegrator = static::getContainer()->get(AliExpressIntegrateOrder::class);
        $order = $aliExpressIntegrator->getApi()->getOrder('3016383805006340');
        $orderBc = $aliExpressIntegrator->transformToAnBcOrder($order);
        $newWebOrder = new WebOrder();
        $newWebOrder->setCompany(GadgetIberiaConnector::GADGET_IBERIA);
        $aliExpressIntegrator->adjustSaleOrder($newWebOrder, $orderBc);
        $this->assertCount(2, $orderBc->salesLines);
        $this->assertEquals(42.49, $orderBc->salesLines[0]->unitPrice);

        $bcConnector = $aliExpressIntegrator->getBusinessCentralConnector(GadgetIberiaConnector::GADGET_IBERIA);
        $orderFinal = $bcConnector->createSaleOrder($orderBc->transformToArray());
        $this->assertIsArray($orderFinal);
        $orderFull = $bcConnector->getFullSaleOrder($orderFinal['id']);
        $this->assertIsArray($orderFull);
        $this->assertCount(2,  $orderFull['salesOrderLines']);
        $this->assertEquals(42.49, $orderFull['totalAmountIncludingTax']);
    }



    public function testTransformationWithCanonDigital(): void
    {
        $aliExpressIntegrator = static::getContainer()->get(AliExpressIntegrateOrder::class);
        $order = $aliExpressIntegrator->getApi()->getOrder('3016383805006340');
        $order->child_order_list->global_aeop_tp_child_order_dto[0]->sku_code = 'X-MZB08KWEU';
        $orderBc = $aliExpressIntegrator->transformToAnBcOrder($order);
        $newWebOrder = new WebOrder();
        $newWebOrder->setCompany(GadgetIberiaConnector::GADGET_IBERIA);
        $aliExpressIntegrator->adjustSaleOrder($newWebOrder, $orderBc);
        $this->assertCount(2, $orderBc->salesLines);
        $this->assertEquals(41.39, $orderBc->salesLines[0]->unitPrice);

        $bcConnector = $aliExpressIntegrator->getBusinessCentralConnector(GadgetIberiaConnector::GADGET_IBERIA);
        $orderFinal = $bcConnector->createSaleOrder($orderBc->transformToArray());
        $this->assertIsArray($orderFinal);
        $orderFull = $bcConnector->getFullSaleOrder($orderFinal['id']);
        $this->assertIsArray($orderFull);
        $this->assertCount(3,  $orderFull['salesOrderLines']);
        $this->assertEquals(42.49, $orderFull['totalAmountIncludingTax']);
    }
}
