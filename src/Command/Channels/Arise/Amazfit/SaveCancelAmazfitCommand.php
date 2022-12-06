<?php

namespace App\Command\Channels\Arise\Amazfit;

use App\Command\Channels\Arise\AriseSaveCancelCommand;
use App\Entity\IntegrationChannel;

class SaveCancelAmazfitCommand extends AriseSaveCancelCommand
{
    protected static $defaultName = 'app:arise-amazfit-cancel-orders';
    protected static $defaultDescription = 'Retrieve all arise orders cancelled online';


    protected function getChannel()
    {
        return IntegrationChannel::CHANNEL_AMAZFIT_ARISE;
    }
}
