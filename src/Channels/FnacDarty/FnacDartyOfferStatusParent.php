<?php

namespace App\Channels\FnacDarty;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\Channels\FnacDarty\FnacDartyApi;
use App\Entity\ProductSaleChannel;
use App\Helper\MailService;
use App\Service\Aggregator\ApiAggregator;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

abstract class FnacDartyOfferStatusParent
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
   
    protected function getFnacDartyApi(): FnacDartyApi
    {
        return $this->apiAggregator->getService($this->getChannel());
    }


    public function checkAllProducts()
    {

       
        $managed = [];

        $products = $this->getAllSaleChannels($this->getChannel());
        $offerMirakls = $this->getFnacDartyApi()->getOffers();
        $this->logger->info(count($offerMirakls).' offers');
        foreach($offerMirakls as $offerMirakl){
            if(array_key_exists($offerMirakl["offer_seller_id"], $products)){
               
                if($offerMirakl["quantity"]>0){
                    $published = true;
                    $reason =null;
                } else {
                    $published = false;
                    $reason ='Out of stock';
                }
                $productChannel = $products[$offerMirakl["offer_seller_id"]];
                $productChannel->setPublished($published);
                $productChannel->setReason($reason);

                $this->logger->info('offer '.$offerMirakl["offer_seller_id"].'>> '.$published ? 'Active ' : 'Inactive ('.$reason.')' );
                $managed[]=$offerMirakl["offer_seller_id"];
            }
        }


        $errorsIntegrations = $this->getLastErrorsFromProductImports();
        foreach($products as $sku => $product){
            if(!in_array($sku, $managed)){
                 $this->logger->error('Not found '.$sku);
                 $productChannel->setPublished(false);
                 $reason= (array_key_exists($sku, $errorsIntegrations)) ? 'Problem import '.$errorsIntegrations[$sku] : "Unknown";
                 $productChannel->setReason($reason);
            }
         }

        $this->manager->flush();    
        $this->updateDisabledPutNull($this->getChannel());


        
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
            $products[$productSku]=$productSalesChannel;
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




    protected function getLastErrorsFromProductImports(){
        $import = $this->getFnacDartyApi()->getLastProductImport();
        if($import->getErrorReport()){
            $errosFiles= $this->getFnacDartyApi()->getReportErrorProduct($import->getImportId());
            $errors = [];
            foreach($errosFiles as $errosFile){
                $errors[$errosFile['SKU_PART']]=$errosFile['errors'];
            }
            return $errors;
        } else {
            return [];
        }
    }




}
