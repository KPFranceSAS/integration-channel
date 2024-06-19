<?php

namespace App\BusinessCentral;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\BusinessCentral\Connector\BusinessCentralConnector;
use Exception;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;

class ProductTaxFinder
{
    protected $logger;
    protected $businessCentralAggregator;
    protected $taxes = [];

    public function __construct(
        LoggerInterface $logger,
        BusinessCentralAggregator $businessCentralAggregator
    ) {
        $this->logger = $logger;
        $this->businessCentralAggregator = $businessCentralAggregator;
    }




    public function getAdditionalTaxes(string $itemId, string $company, string $shippingCountry, ?string $billingCountry = null): float
    {
        $taxes = 0;
        $bcConnector= $this->getBusinessCentralConnector($company);
        $item = $bcConnector->getItem($itemId);
        $taxes +=$this->getCanonDigitalForItem($item, $company, $shippingCountry, $billingCountry);
        $taxes +=$this->getEcoTaxForItem($item, $company, $shippingCountry, $billingCountry);
        return $taxes;
    }





    public function getCanonDigitalForItem(array $item, string $company, string $shippingCountry, ?string $billingCountry = null): float
    {
        if ($shippingCountry == 'ES' || $billingCountry == 'ES') {
            $bcConnector= $this->getBusinessCentralConnector($company);
            if ($item && $item['DigitalCopyTax'] && strlen((string) $item['CanonDigitalCode'])>0) {
                $taxes = $bcConnector->getTaxesByCodeAndByFeeType($item['CanonDigitalCode'], 'Canon Digital');
                if ($taxes) {
                    $this->logger->info('Canon digital de ' . $taxes['UnitPrice'] . ' for ' . $item['number']);
                    return $taxes['UnitPrice'];
                } else {
                    $this->logger->info('No canon digital found for ' . $item['CanonDigitalCode']);
                }
            } else {
                $this->logger->info('No canon digital for ' . $item['number']);
            }
        } else {
            $this->logger->info('No canon digital in '.$shippingCountry.' or '.$billingCountry);
        }
        return 0;
    }




    public function getEcoTaxForItem(array $item, string $company, string $shippingCountry, ?string $billingCountry = null): float
    {
        if ($shippingCountry == 'FR' || $billingCountry == 'FR') {
            $bcConnector= $this->getBusinessCentralConnector($company);
            
            if ($item && $item['WEEE'] && strlen((string) $item['WEEEcategorycode'])>0) {
                $taxes = $bcConnector->getTaxesByCodeAndByFeeType($item['WEEEcategorycode'], 'WEEE');
                if ($taxes) {
                    $this->logger->info('Ecotax de ' . $taxes['UnitPrice'] . ' for ' . $item['number']);
                    return $taxes['UnitPrice'];
                } else {
                    $this->logger->info('No Ecotax found for ' . $item['WEEEcategorycode']);
                }
            } else {
                $this->logger->info('No Ecotax for ' . $item['number']);
            }
        } else {
            $this->logger->info('No Ecotax in '.$shippingCountry.' or '.$billingCountry);
        }
        return 0;
    }



    


    public function getEcoTax($sku, $company, $country){
        $key = 'DEEE_'.$sku.'_'.$company.'_'.$country;

        if(!array_key_exists($key, $this->taxes)){
            $businessCentralConnector = $this->businessCentralAggregator->getBusinessCentralConnector($company);

            $itemBc = $businessCentralConnector->getItemByNumber($sku);
            if($itemBc) {
                $ecotaxes =  $this->getEcoTaxForItem(
                    $itemBc,
                    $company,
                    $country
                );
                $this->taxes[$key] = $ecotaxes;
            } else {
                $this->taxes[$key] = 0;
            }
        }
        return  $this->taxes[$key];
    }
    



   


    public function getBusinessCentralConnector($companyName): BusinessCentralConnector
    {
        return $this->businessCentralAggregator->getBusinessCentralConnector($companyName);
    }
}
