<?php

namespace App\Command\Channels\Arise\Amazfit;

use App\Command\Channels\Arise\AriseMarkAsDeliveryCommand;
use App\Entity\IntegrationChannel;

class MarkDeliveryAmazfitCommand extends AriseMarkAsDeliveryCommand
{
    protected static $defaultName = 'app:arise-amazfit-delivery-orders';
    protected static $defaultDescription = 'Mark all arise orders delivery online';


    protected function getChannel()
    {
        return IntegrationChannel::CHANNEL_AMAZFIT_ARISE;
    }
}
