<?php

namespace App\Service\Integrator;

use App\Entity\WebOrder;
use App\Helper\BusinessCentral\Model\SaleOrder;


interface IntegratorInterface
{


    public function integrateOrder($order);

    public function reIntegrateOrder(WebOrder $order);

    public function transformToAnBcOrder($orderApi): SaleOrder;

    public function processOrders($reIntegrate = false);
}
