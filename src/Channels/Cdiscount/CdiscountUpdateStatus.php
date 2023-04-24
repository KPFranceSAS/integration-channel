<?php

namespace App\Channels\Cdiscount;

use App\Channels\Cdiscount\CdiscountApi;
use App\Entity\IntegrationChannel;
use App\Service\Aggregator\UpdateStatusParent;

/**
 * Services that will get through the API the order from Cdiscount
 *
 */
class CdiscountUpdateStatus extends UpdateStatusParent
{
    protected function getChannelApi(): CdiscountApi
    {
        return $this->getApi();
    }


    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_CDISCOUNT;
    }

}
