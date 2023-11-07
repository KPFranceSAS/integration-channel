<?php

namespace App\BusinessCentral;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Entity\LogisticClass;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

class LogisticClassFinder
{
    protected $logger;

    protected $connector;

    protected $manager;


    public function __construct(
                LoggerInterface $logger, 
                BusinessCentralAggregator $businessCentralAggregator,
                ManagerRegistry $managerRegistry)
    {
        $this->logger = $logger;
        $this->connector = $businessCentralAggregator->getBusinessCentralConnector(BusinessCentralConnector::KIT_PERSONALIZACION_SPORT);
        $this->manager = $managerRegistry->getManager();
    }



    public function getBestLogisiticClass($sku){
        $this->logger->info("Check best logistic class for ".$sku);
        $measurement =  $this->connector->getItemUnitOfMeasure($sku);
        if ($measurement && $measurement['WeightGross']>0) {
            $logisticClasses = $this->getAllLogisticClasses();
            foreach( $logisticClasses as $logisticClass ) {
                if($logisticClass->getMaximumWeight()>=$measurement['WeightGross'] &&  
                    $logisticClass->getMinimumWeight()<=$measurement['WeightGross']) {
                        return $logisticClass;
                }
            }
        }
        return null;      
    }

    private $logisticClasses;

    public function getAllLogisticClasses(){
        if(!$this->logisticClasses){
            $this->logisticClasses = $this->manager->getRepository(LogisticClass::class)->findAll();
        }
        return $this->logisticClasses;
    }

   


}
