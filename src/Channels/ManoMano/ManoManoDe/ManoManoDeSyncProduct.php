<?php

namespace App\Channels\ManoMano\ManoManoDe;

use App\Channels\ManoMano\ManoManoSyncProductParent;
use App\Entity\IntegrationChannel;

class ManoManoDeSyncProduct extends ManoManoSyncProductParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_MANOMANO_DE;
    }


    public function getChannelPim(): string
    {
        return 'manomano_de_kp';
    }


    public function getLocale(): string
    {
        return 'de_DE';
    }
}
