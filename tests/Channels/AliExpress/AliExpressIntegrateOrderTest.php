<?php

namespace App\Tests\Channels\AliExpress;

use App\BusinessCentral\Connector\GadgetIberiaConnector;
use App\Channels\AliExpress\AliExpress\AliExpressIntegrateOrder;
use App\Entity\WebOrder;
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
        $this->assertCount(2, $orderFull['salesOrderLines']);
        $this->assertEquals(42.49, $orderFull['totalAmountIncludingTax']);
        $this->assertSame($orderFull['shippingAgent'], "DHL PARCEL");
        $this->assertSame($orderFull['shippingAgentService'], "DHL1");
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
        $this->assertCount(3, $orderFull['salesOrderLines']);
        $this->assertEquals(42.49, $orderFull['totalAmountIncludingTax']);
        $this->assertSame($orderFull['shippingAgent'], "DHL PARCEL");
        $this->assertSame($orderFull['shippingAgentService'], "DHL1");
    }


    public function testTransformationWithMadrid(): void
    {
        $aliExpressIntegrator = static::getContainer()->get(AliExpressIntegrateOrder::class);
        $order = $aliExpressIntegrator->getApi()->getOrder('8150568256560603');
        $order->receipt_address->country = 'ES';
        $orderBc = $aliExpressIntegrator->transformToAnBcOrder($order);
        $newWebOrder = new WebOrder();
        $newWebOrder->setCompany(GadgetIberiaConnector::GADGET_IBERIA);
        $aliExpressIntegrator->adjustSaleOrder($newWebOrder, $orderBc);
        $this->assertCount(2, $orderBc->salesLines);
        $this->assertEquals(399, $orderBc->salesLines[0]->unitPrice);

        $bcConnector = $aliExpressIntegrator->getBusinessCentralConnector(GadgetIberiaConnector::GADGET_IBERIA);
        $orderFinal = $bcConnector->createSaleOrder($orderBc->transformToArray());
        $this->assertIsArray($orderFinal);
        $orderFull = $bcConnector->getFullSaleOrder($orderFinal['id']);
        $this->assertIsArray($orderFull);
        $this->assertCount(2, $orderFull['salesOrderLines']);
        $this->assertEquals(416.89, $orderFull['totalAmountIncludingTax']);
        $this->assertSame($orderFull['shippingAgent'], "DHLB2C");
        $this->assertSame($orderFull['shippingAgentService'], "DHLB2C");
        $this->assertSame($orderFull['locationCode'], "MADRID");
    }
}
