<?php

namespace App\Service\Integrator;

use App\Entity\WebOrder;
use App\Helper\BusinessCentral\Model\SaleOrder;
use stdClass;


interface IntegratorInterface
{


    public function integrateOrder(stdClass $order);

    public function reIntegrateOrder(WebOrder $order);

    public function transformToAnBcOrder(stdClass $orderApi): SaleOrder;

    public function processOrders($reIntegrate = false);
}
