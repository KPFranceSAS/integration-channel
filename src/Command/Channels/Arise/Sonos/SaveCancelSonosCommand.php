<?php

namespace App\Command\Channels\Arise\Sonos;

use App\Command\Channels\Arise\AriseSaveCancelCommand;
use App\Entity\IntegrationChannel;

class SaveCancelSonosCommand extends AriseSaveCancelCommand
{
    protected static $defaultName = 'app:arise-sonos-cancel-orders';
    protected static $defaultDescription = 'Retrieve all sonos arise orders cancelled online';


    protected function getChannel()
    {
        return IntegrationChannel::CHANNEL_SONOS_ARISE;
    }
}
