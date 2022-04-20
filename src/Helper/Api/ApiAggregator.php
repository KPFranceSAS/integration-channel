<?php

namespace App\Helper\Api;

use Akeneo\Pim\ApiClient\Api\ChannelApi;
use App\Entity\WebOrder;
use App\Service\AliExpress\AliExpressApi;
use App\Service\ChannelAdvisor\ChannelWebservice;
use App\Service\FitbitExpress\FitbitExpressApi;
use App\Service\OwletCare\OwletCareApi;
use Exception;

class ApiAggregator
{

    private $aliExpressApi;

    private $fitbitExpressApi;

    private $owletCareApi;

    private $channelWebservice;

    public function __construct(
        AliExpressApi $aliExpressApi,
        FitbitExpressApi $fitbitExpressApi,
        OwletCareApi $owletCareApi,
        ChannelWebservice $channelWebservice
    ) {
        $this->aliExpressApi = $aliExpressApi;
        $this->fitbitExpressApi = $fitbitExpressApi;
        $this->owletCareApi = $owletCareApi;
        $this->channelWebservice = $channelWebservice;
    }


    public  function getApi(string $channel)
    {

        if ($channel == WebOrder::CHANNEL_CHANNELADVISOR) {
            return $this->channelWebservice;
        }

        if ($channel == WebOrder::CHANNEL_ALIEXPRESS) {
            return $this->aliExpressApi;
        }

        if ($channel == WebOrder::CHANNEL_OWLETCARE) {
            return $this->owletCareApi;
        }

        if ($channel == WebOrder::CHANNEL_FITBITEXPRESS) {
            return $this->fitbitExpressApi;
        }

        throw new Exception("Channel $channel is not related to any api");
    }
}
