<?php

namespace App\Command\Channels\AliExpress\AliExpress;

use App\Command\Channels\AliExpress\AliExpressSaveCancelCommand;
use App\Entity\IntegrationChannel;

#[\Symfony\Component\Console\Attribute\AsCommand('app:aliexpress-cancel-orders', 'Retrieve all Aliexpress orders cancelled online')]
class SaveCancelGadgetCommand extends AliExpressSaveCancelCommand
{
    protected function getChannel()
    {
        return IntegrationChannel::CHANNEL_ALIEXPRESS;
    }
}
