<?php

namespace App\Channels\FnacDarty\FnacFr;

use App\Channels\FnacDarty\FnacDartyApi;
use App\Entity\IntegrationChannel;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class FnacFrApi extends FnacDartyApi
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_FNAC_FR;
    }

    
    public function __construct(
        LoggerInterface $logger,
        Environment $twig,
        $fnacFrClientUrl,
        $fnacFrClientPartnerId,
        $fnacFrClientShopId,
        $fnacFrClientKey,
        $projectDir,
        $fnacFrMiraklClientUrl,
        $fnacFrMiraklClientKey
    ) {
        
        parent::__construct( $logger,
                            $twig,
                            $fnacFrClientUrl,
                            $fnacFrClientPartnerId,
                            $fnacFrClientShopId,
                            $fnacFrClientKey,
                            $projectDir,
                            $fnacFrMiraklClientUrl,
                            $fnacFrMiraklClientKey);
    }




}
