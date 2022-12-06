<?php

namespace App\Command\Channels\Arise\Gadget;

use App\Command\Channels\Arise\AriseSaveCancelCommand;
use App\Entity\IntegrationChannel;

class SaveCancelGadgetCommand extends AriseSaveCancelCommand
{
    protected static $defaultName = 'app:arise-gadget-cancel-orders';
    protected static $defaultDescription = 'Retrieve all arise orders cancelled online';


    protected function getChannel()
    {
        return IntegrationChannel::CHANNEL_ARISE;
    }
}
