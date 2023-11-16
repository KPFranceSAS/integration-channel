<?php

namespace App\Channels\FnacDarty\DartyFr;

use App\Channels\FnacDarty\FnacDartyApi;
use App\Entity\IntegrationChannel;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class DartyFrApi extends FnacDartyApi
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_DARTY_FR;
    }

    
    public function __construct(
        LoggerInterface $logger,
        Environment $twig,
        $dartyFrClientUrl,
        $dartyFrClientPartnerId,
        $dartyFrClientShopId,
        $dartyFrClientKey,
        $projectDir,
        $dartyFrMiraklClientUrl,
        $dartyFrMiraklClientKey
    ) {
        
        parent::__construct(
            $logger,
            $twig,
            $dartyFrClientUrl,
            $dartyFrClientPartnerId,
            $dartyFrClientShopId,
            $dartyFrClientKey,
            $projectDir,
            $dartyFrMiraklClientUrl,
            $dartyFrMiraklClientKey
        );
    }




}
