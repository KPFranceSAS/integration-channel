<?php

namespace App\Command\Channels\Arise\Imou;

use App\Command\Channels\Arise\AriseSaveCancelCommand;
use App\Entity\IntegrationChannel;

#[\Symfony\Component\Console\Attribute\AsCommand('app:arise-imou-cancel-orders', 'Retrieve all arise imou orders cancelled online')]
class SaveCancelImouCommand extends AriseSaveCancelCommand
{
    protected function getChannel()
    {
        return IntegrationChannel::CHANNEL_IMOU_ARISE;
    }
}
