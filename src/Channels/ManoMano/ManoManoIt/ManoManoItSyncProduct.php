<?php

namespace App\Channels\ManoMano\ManoManoIt;

use App\Channels\ManoMano\ManoManoSyncProductParent;
use App\Entity\IntegrationChannel;

class ManoManoItSyncProduct extends ManoManoSyncProductParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_MANOMANO_IT;
    }


    public function getChannelPim(): string
    {
        return 'manomano_it_kp';
    }


    public function getLocale(): string
    {
        return 'it_IT';
    }
}
