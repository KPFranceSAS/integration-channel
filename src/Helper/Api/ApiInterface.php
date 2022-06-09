<?php

namespace App\Helper\Api;

interface ApiInterface
{
    public function getChannel();

    public function getOrder(string $orderNumber);

    public function getAllOrdersToSend();
}
