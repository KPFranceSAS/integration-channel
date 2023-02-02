<?php

namespace App\Tests\Channels\ChannelAdvisor;

use App\BusinessCentral\Connector\GadgetIberiaConnector;
use App\Channels\ChannelAdvisor\ChannelAdvisorIntegrateOrder;
use App\Entity\WebOrder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ChannelIntegrateOrderTest extends KernelTestCase
{
    public function testTransformationClassic(): void
    {
        $channelIntegrator = static::getContainer()->get(ChannelAdvisorIntegrateOrder::class);

        $params = "PaymentStatus eq 'Cleared' and CheckoutStatus eq 'Completed' and CreatedDateUtc gt 2023-01-01 and BillingCountry eq 'ES' and CreatedDateUtc lt 2023-01-03";

        $orders = $channelIntegrator->getApi()->getAllOrdersBy($params);
        $i=0;

        foreach ($orders as $order) {
            $orderBc = $channelIntegrator->transformToAnBcOrder($order);
            $orderBc->customerNumber = '003315';
            $newWebOrder = new WebOrder();
            $newWebOrder->setCompany(GadgetIberiaConnector::GADGET_IBERIA);
            $channelIntegrator->adjustSaleOrder($newWebOrder, $orderBc);
    
            $bcConnector = $channelIntegrator->getBusinessCentralConnector(GadgetIberiaConnector::GADGET_IBERIA);
            $orderFinal = $bcConnector->createSaleOrder($orderBc->transformToArray());
            $this->assertIsArray($orderFinal);
            $orderFull = $bcConnector->getFullSaleOrder($orderFinal['id']);
            $this->assertIsArray($orderFull);
            $i++;
        }
    }
}
