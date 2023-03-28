<?php

namespace App\Channels\ManoMano\ManoManoEs;

use App\Channels\ManoMano\ManoManoSyncProductParent;
use App\Entity\IntegrationChannel;

class ManoManoEsSyncProduct extends ManoManoSyncProductParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_MANOMANO_ES;
    }


    public function getChannelPim(): string
    {
        return 'manomano_es_kp';
    }


    public function getLocale(): string
    {
        return 'es_ES';
    }
}
