<?php

namespace App\Channels\Mirakl;

use App\Channels\Mirakl\MiraklApiParent;
use App\Entity\Product;
use App\Helper\MailService;
use App\Service\Aggregator\ApiAggregator;
use App\Service\Aggregator\PriceParent;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

abstract class MiraklPriceParent extends PriceParent
{
    protected $projectDir;


    public function __construct(
        ManagerRegistry $manager,
        LoggerInterface $logger,
        MailService $mailer,
        ApiAggregator $apiAggregator,
        $projectDir
    ) {
        parent::__construct($manager, $logger, $mailer, $apiAggregator);
        $this->projectDir =  $projectDir.'/var/export/'.$this->getLowerChannel().'/';
    }

    protected function getLowerChannel()
    {
        return strtolower($this->getChannel());
    }


    protected function getMiraklApi(): MiraklApiParent
    {
        return $this->getApi();
    }

    


    
}
