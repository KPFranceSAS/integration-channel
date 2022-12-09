<?php

namespace App\Command\Channels\Arise\Gadget;

use App\Command\Channels\Arise\AriseMarkAsDeliveryCommand;
use App\Entity\IntegrationChannel;

class MarkDeliveryGadgetCommand extends AriseMarkAsDeliveryCommand
{
    protected static $defaultName = 'app:arise-gagdet-delivery-orders';
    protected static $defaultDescription = 'Mark all gagdet orders delivery online';


    protected function getChannel()
    {
        return IntegrationChannel::CHANNEL_ARISE;
    }
}
