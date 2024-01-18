<?php

namespace App\Channels\FnacDarty\FnacFr;

use App\Channels\FnacDarty\FnacDartySyncProduct;
use App\Entity\IntegrationChannel;

class FnacFrSyncProduct extends FnacDartySyncProduct
{
    protected function getLocalePim(): string
    {
        return 'fr_FR';
    }


    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_FNAC_FR;
    }
}
