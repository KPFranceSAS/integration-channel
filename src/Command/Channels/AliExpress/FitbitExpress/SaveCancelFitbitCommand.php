<?php

namespace App\Command\Channels\AliExpress\FitbitExpress;

use App\Command\Channels\AliExpress\AliExpressSaveCancelCommand;
use App\Entity\IntegrationChannel;

#[\Symfony\Component\Console\Attribute\AsCommand('app:fitbitexpress-cancel-orders', 'Retrieve all fitbitexpress orders cancelled online')]
class SaveCancelFitbitCommand extends AliExpressSaveCancelCommand
{
    protected function getChannel()
    {
        return IntegrationChannel::CHANNEL_FITBITEXPRESS;
    }
}
