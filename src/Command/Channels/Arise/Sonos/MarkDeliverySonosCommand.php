<?php

namespace App\Command\Channels\Arise\Sonos;

use App\Command\Channels\Arise\AriseMarkAsDeliveryCommand;
use App\Entity\IntegrationChannel;

class MarkDeliverySonosCommand extends AriseMarkAsDeliveryCommand
{
    protected static $defaultName = 'app:arise-sonos-delivery-orders';
    protected static $defaultDescription = 'Mark all arise orders delivery online';


    protected function getChannel()
    {
        return IntegrationChannel::CHANNEL_SONOS_ARISE;
    }
}
