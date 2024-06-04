<?php

namespace App\Channels\Mirakl;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\Channels\Mirakl\MiraklApiParent;
use App\Entity\ProductSaleChannel;
use App\Helper\MailService;
use App\Service\Aggregator\ApiAggregator;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

abstract class MiraklOfferStatusParent
{
    /**@var EntityManager */
    protected $manager;


    public function __construct(
        ManagerRegistry $manager,
        protected LoggerInterface $logger,
        protected MailService $mailer,
        protected BusinessCentralAggregator $businessCentralAggregator,
        protected ApiAggregator $apiAggregator,

    ) {
        $this->manager = $manager->getManager();
    }

    abstract public function getChannel(): string;


    protected function getMiraklApi(): MiraklApiParent
    {
        return $this->apiAggregator->getApi($this->getChannel());
    }


    public function checkAllProducts()
    {

       
        $managed = [];

        $products = $this->getAllSaleChannels($this->getChannel());
        $offerMirakls = $this->getMiraklApi()->getOffers();
        $this->logger->info(count($offerMirakls).' offers');
        foreach($offerMirakls as $offerMirakl){
            if(array_key_exists($offerMirakl["sku"], $products)){
               
                $published = $offerMirakl["active"];
                $reason = $published==false ? implode(',', $offerMirakl['inactivity_reasons']) : null;

                foreach($products[$offerMirakl["sku"]] as $productChannel){
                    $productChannel->setPublished($published);
                    $productChannel->setReason($reason);
                }

                $this->logger->info('offer '.$offerMirakl["sku"].'>> '.$published ? 'Active ' : 'Inactive ('.$reason.')' );
                $managed[]=$offerMirakl["sku"];
            }
        }

        $errors = $this->getLastErrorsFromOfferImports();
        if(count($errors)>0){
           $errorsIntegrations = $this->getLastErrorsFromProductImports();
           foreach($errors as $sku => $error){
                 $reason = $error;
                 if(array_key_exists($sku, $errorsIntegrations)){
                    $reason.=' >> Problem import '.$errorsIntegrations[$sku];
                 }

                 if(array_key_exists($sku, $products)){
                        foreach($products[$sku] as $productChannel){
                            $productChannel->setPublished(false);
                            $productChannel->setReason($reason);
                        }
                    }
                $this->logger->info('Product '.$sku.'>> Inactive ('.$reason.')' );    
                $managed[]=$sku;
           
           }  
        }
        $this->manager->flush();    
        $this->updateDisabledPutNull($this->getChannel());


        foreach($products as $sku => $product){
           if(!in_array($sku, $managed)){
                $this->logger->error('Not found '.$sku);
           }
        }
    }



    protected function getAllSaleChannels($code){
        $queryBuilder = $this->manager->createQueryBuilder();

        $queryBuilder->select('productsalechannel')
            ->from(ProductSaleChannel::class, 'productsalechannel')
            ->leftJoin('productsalechannel.saleChannel', 'salechannel')
            ->leftJoin('salechannel.integrationChannel', 'integrationchannel')
            ->where('productsalechannel.enabled = 1')
            ->andWhere('integrationchannel.code = :code')
            ->setParameter('code', $code);

        $productSalesChannels = $queryBuilder->getQuery()->getResult();
        $products= [];
        foreach($productSalesChannels as $productSalesChannel){
            $productSku = $productSalesChannel->getProduct()->getSku();
            if(!array_key_exists($productSku, $products)){
                $products[$productSku]=[];
            }
            $products[$productSku][]=$productSalesChannel;
        }

        return $products;
    }

    protected function updateDisabledPutNull($code){

        $subQueryBuilder = $this->manager->createQueryBuilder();
        $subQueryBuilder->select('productsalechannel.id')
                ->from(ProductSaleChannel::class, 'productsalechannel')
                ->leftJoin('productsalechannel.saleChannel', 'salechannel')
                ->leftJoin('salechannel.integrationChannel', 'integrationchannel')
                ->where('productsalechannel.enabled = 0')
                ->andWhere($subQueryBuilder->expr()->isNotNull('productsalechannel.published'))
                ->andWhere('integrationchannel.code = :code')
                ->setParameter('code', $code);

        $ids = $subQueryBuilder->getQuery()->getArrayResult();
        $ids = array_column($ids, 'id');

        if (count($ids)>0){
            $queryBuilder = $this->manager->createQueryBuilder();        
            $queryBuilder->update(ProductSaleChannel::class, 'productsalechannelQuery')
            ->set('productsalechannelQuery.published', ':nullValue')
            ->set('productsalechannelQuery.reason', ':nullValue')
            ->where($queryBuilder->expr()->in('productsalechannelQuery.id', $ids))
            ->setParameter('nullValue', null)
            ->getQuery()->execute();
        }
       
    }



    protected function getLastErrorsFromOfferImports(): array{
        $imports = $this->getMiraklApi()->getLastOfferImports();
        foreach($imports as $import){
            if($import->getLinesInError()>0){
                $errosFiles= $this->getMiraklApi()->getReportErrorOffer($import->getImportId());
                $errors = [];
                foreach($errosFiles as $errosFile){
                    $errors[$errosFile['sku']]=$errosFile['error-message'];
                }
                return $errors;
            } else {
                return [];
            }
        }
    }


    abstract protected function getIdentifier();


    protected function getLastErrorsFromProductImports(){
        $import = $this->getMiraklApi()->getLastProductImport();
        if($import->getErrorReport()){
            $errosFiles= $this->getMiraklApi()->getReportErrorProduct($import->getImportId());
            $errors = [];
            foreach($errosFiles as $errosFile){
                $errors[$errosFile[$this->getIdentifier()]]=$errosFile['errors'];
            }
            return $errors;
        } else {
            return [];
        }
    }




}
