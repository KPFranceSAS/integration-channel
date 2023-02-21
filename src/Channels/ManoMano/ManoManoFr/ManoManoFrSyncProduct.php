<?php

namespace App\Channels\ManoMano\ManoManoFr;

use App\Channels\ManoMano\ManoManoSyncProductParent;
use App\Entity\IntegrationChannel;

class ManoManoFrSyncProduct extends ManoManoSyncProductParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_MANOMANO_FR;
    }


    public function getChannelPim(): string
    {
        return 'manomano_fr_kp';
    }


    public function getLocale(): string
    {
        return 'fr_FR';
    }
}
