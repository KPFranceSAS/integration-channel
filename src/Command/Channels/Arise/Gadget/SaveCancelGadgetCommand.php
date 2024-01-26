<?php

namespace App\Command\Channels\Arise\Gadget;

use App\Command\Channels\Arise\AriseSaveCancelCommand;
use App\Entity\IntegrationChannel;

#[\Symfony\Component\Console\Attribute\AsCommand('app:arise-gadget-cancel-orders', 'Retrieve all arise orders cancelled online')]
class SaveCancelGadgetCommand extends AriseSaveCancelCommand
{
    protected function getChannel()
    {
        return IntegrationChannel::CHANNEL_ARISE;
    }
}
