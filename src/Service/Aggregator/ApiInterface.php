<?php

namespace App\Service\Aggregator;

interface ApiInterface
{
    public function getChannel();

    public function getOrder(string $orderNumber);

    public function getAllOrdersToSend();
}
