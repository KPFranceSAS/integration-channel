<?php

namespace App\Command\Channels\Arise\Gadget;

use App\Command\Channels\Arise\Amazfit\SaveCancelCommand;
use App\Entity\IntegrationChannel;

class SaveCancelGadgetCommand extends SaveCancelCommand
{
    protected static $defaultName = 'app:arise-cancel-orders';
    protected static $defaultDescription = 'Retrieve all arise orders cancelled online';


    protected function getChannel()
    {
        return IntegrationChannel::CHANNEL_ARISE;
    }
}
