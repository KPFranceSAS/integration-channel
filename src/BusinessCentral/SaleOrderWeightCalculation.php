<?php

namespace App\BusinessCentral;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\BusinessCentral\Model\SaleOrder;
use App\BusinessCentral\Model\SaleOrderLine;
use Psr\Log\LoggerInterface;

class SaleOrderWeightCalculation
{
    protected $logger;

    protected $businessCentralAggregator;


    public function __construct(LoggerInterface $logger, BusinessCentralAggregator $businessCentralAggregator)
    {
        $this->logger = $logger;
        $this->businessCentralAggregator = $businessCentralAggregator;
    }



    public function calculateWeight(SaleOrder $saleOrder): float
    {
        $weight = 0;
        foreach($saleOrder->salesLines as $saleLine) {
            if($saleLine->lineType == SaleOrderLine::TYPE_ITEM) {
                $itemBc = $this->getConnector()->getItem($saleLine->itemId);
                
                if($itemBc) {
                    $weight+= $this->calculateWeightLine($itemBc, $saleLine->quantity);
                }
            }
        }
        return ceil($weight);
    }

    
    protected function calculateWeightLine(array $itemBc, int $quantity): float
    {
        if ($this->isBundle($itemBc)) {
            $this->logger->info('Sku '.$itemBc['number'].' is bundle');
            $weightArticle =  $this->getWeightBundle($itemBc['number']);
        } else {
            $weightArticle = $this->getWeightSku($itemBc['number']);
        }

        return $weightArticle*$quantity;
    }
   



    /**
     * Returns the level of stock of bundle product
     */
    protected function getWeightBundle($sku): float
    {
        $weightBundle = 0;
        $components = $this->getConnector()->getComponentsBundle($sku);
        foreach ($components as $component) {
            if ($component['Quantity'] > 0) {
                $weightComponent = $this->getWeightSku($component['ComponentSKU']);
                $weightBundle += $weightComponent*$component['Quantity'];
            }
        }
        $this->logger->info("Bundle  ".$sku.' has weight '.$weightBundle);
        return $weightBundle;
    }



    /**
     * Check if it is a bundle
     */
    protected function isBundle(array $item): bool
    {
        if ($item['AssemblyBOM']==false) {
            return false;
        }

        if ($item['AssemblyBOM']==true && in_array($item['AssemblyPolicy'], ["Assemble-to-Stock", "Ensamblar para stock"])) {
            return false;
        }
        return true;
    }


    protected function getWeightSku($sku): float
    {
        $measurement =  $this->getConnector()->getItemUnitOfMeasure($sku);
        return  ($measurement && $measurement['WeightGross']>0) ? $measurement['WeightGross'] : 0;
    }
    


    protected function getConnector()
    {
        return $this->businessCentralAggregator->getBusinessCentralConnector(BusinessCentralConnector::KIT_PERSONALIZACION_SPORT);
    }




}
