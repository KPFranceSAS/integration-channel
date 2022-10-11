<?php

namespace App\Command\Channels\AliExpress\FitbitExpress;

use App\Command\Channels\AliExpress\AliExpress\SaveCancelCommand;
use App\Entity\IntegrationChannel;

class SaveCancelFitbitCommand extends SaveCancelCommand
{
    protected static $defaultName = 'app:fitbitexpress-cancel-orders';
    protected static $defaultDescription = 'Retrieve all fitbitexpress orders cancelled online';


    protected function getChannel()
    {
        return IntegrationChannel::CHANNEL_FITBITEXPRESS;
    }
}
