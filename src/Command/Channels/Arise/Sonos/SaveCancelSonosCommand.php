<?php

namespace App\Command\Channels\Arise\Sonos;

use App\Command\Channels\Arise\AriseSaveCancelCommand;
use App\Entity\IntegrationChannel;

#[\Symfony\Component\Console\Attribute\AsCommand('app:arise-sonos-cancel-orders', 'Retrieve all arise sonos orders cancelled online')]
class SaveCancelSonosCommand extends AriseSaveCancelCommand
{
    protected function getChannel()
    {
        return IntegrationChannel::CHANNEL_SONOS_ARISE;
    }
}
