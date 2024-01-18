<?php

namespace App\Channels\FnacDarty\DartyFr;

use App\Channels\FnacDarty\FnacDartySyncProduct;
use App\Entity\IntegrationChannel;

class DartyFrSyncProduct extends FnacDartySyncProduct
{

    protected function getLocalePim(): string
    {
        return 'fr_FR';
    }


    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_DARTY_FR;
    }
}
