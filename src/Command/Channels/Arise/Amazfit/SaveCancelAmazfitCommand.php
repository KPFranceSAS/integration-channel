<?php

namespace App\Command\Channels\Arise\Amazfit;

use App\Command\Channels\Arise\AriseSaveCancelCommand;
use App\Entity\IntegrationChannel;

#[\Symfony\Component\Console\Attribute\AsCommand('app:arise-amazfit-cancel-orders', 'Retrieve all arise orders cancelled online')]
class SaveCancelAmazfitCommand extends AriseSaveCancelCommand
{
    protected function getChannel()
    {
        return IntegrationChannel::CHANNEL_AMAZFIT_ARISE;
    }
}
