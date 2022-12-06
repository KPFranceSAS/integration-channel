<?php

namespace App\Command\Channels\AliExpress\AliExpress;

use App\Command\Channels\AliExpress\AliExpressSaveCancelCommand;
use App\Entity\IntegrationChannel;

class SaveCancelGadgetCommand extends AliExpressSaveCancelCommand
{
    protected static $defaultName = 'app:aliexpress-cancel-orders';
    protected static $defaultDescription = 'Retrieve all Aliexpress orders cancelled online';



    protected function getChannel()
    {
        return IntegrationChannel::CHANNEL_ALIEXPRESS;
    }
}
