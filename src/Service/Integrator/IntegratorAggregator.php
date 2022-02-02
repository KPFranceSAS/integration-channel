<?php

namespace App\Service\Integrator;

use App\Entity\WebOrder;
use App\Service\AliExpress\AliExpressIntegrateOrder;
use App\Service\ChannelAdvisor\IntegrateOrdersChannelAdvisor;
use App\Service\Integrator\IntegratorInterface;
use Exception;

class IntegratorAggregator
{

    private $aliExpressIntegrateOrder;

    private $integrateOrdersChannelAdvisor;

    public function __construct(IntegrateOrdersChannelAdvisor $integrateOrdersChannelAdvisor, AliExpressIntegrateOrder $aliExpressIntegrateOrder)
    {
        $this->integrateOrdersChannelAdvisor = $integrateOrdersChannelAdvisor;
        $this->aliExpressIntegrateOrder = $aliExpressIntegrateOrder;
    }


    public  function getIntegrator(string $channel): IntegratorInterface
    {

        if ($channel == WebOrder::CHANNEL_CHANNELADVISOR) {
            return $this->integrateOrdersChannelAdvisor;
        } else if ($channel == WebOrder::CHANNEL_ALIEXPRESS) {
            return $this->aliExpressIntegrateOrder;
        }

        throw new Exception("Channel $channel is not related to any integrator");
    }



    public function getAllChannels(): array
    {
        return [
            WebOrder::CHANNEL_CHANNELADVISOR,
            WebOrder::CHANNEL_ALIEXPRESS
        ];
    }
}
