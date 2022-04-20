<?php

namespace App\Helper\Integrator;

use App\Entity\WebOrder;
use App\Service\AliExpress\AliExpressIntegrateOrder;
use App\Service\ChannelAdvisor\IntegrateOrdersChannelAdvisor;
use App\Service\FitbitExpress\FitbitExpressIntegrateOrder;
use App\Helper\Integrator\IntegratorInterface;
use App\Service\OwletCare\OwletCareIntegrateOrder;
use Exception;

class IntegratorAggregator
{

    private $aliExpressIntegrateOrder;

    private $integrateOrdersChannelAdvisor;

    private $owletCareIntegrateOrder;

    private $fitbitExpressIntegrateOrder;

    public function __construct(
        IntegrateOrdersChannelAdvisor $integrateOrdersChannelAdvisor,
        AliExpressIntegrateOrder $aliExpressIntegrateOrder,
        FitbitExpressIntegrateOrder $fitbitExpressIntegrateOrder,
        OwletCareIntegrateOrder $owletCareIntegrateOrder
    ) {
        $this->integrateOrdersChannelAdvisor = $integrateOrdersChannelAdvisor;
        $this->aliExpressIntegrateOrder = $aliExpressIntegrateOrder;
        $this->owletCareIntegrateOrder = $owletCareIntegrateOrder;
        $this->fitbitExpressIntegrateOrder = $fitbitExpressIntegrateOrder;
    }


    public  function getIntegrator(string $channel): IntegratorInterface
    {

        if ($channel == WebOrder::CHANNEL_CHANNELADVISOR) {
            return $this->integrateOrdersChannelAdvisor;
        } else if ($channel == WebOrder::CHANNEL_ALIEXPRESS) {
            return $this->aliExpressIntegrateOrder;
        } else if ($channel == WebOrder::CHANNEL_OWLETCARE) {
            return $this->owletCareIntegrateOrder;
        } else if ($channel == WebOrder::CHANNEL_FITBITEXPRESS) {
            return $this->fitbitExpressIntegrateOrder;
        }

        throw new Exception("Channel $channel is not related to any integrator");
    }



    public function getAllChannels(): array
    {
        return [
            WebOrder::CHANNEL_CHANNELADVISOR,
            WebOrder::CHANNEL_ALIEXPRESS,
            WebOrder::CHANNEL_OWLETCARE,
            WebOrder::CHANNEL_FITBITEXPRESS
        ];
    }
}
