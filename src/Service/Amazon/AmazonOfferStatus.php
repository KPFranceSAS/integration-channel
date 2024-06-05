<?php

namespace App\Service\Amazon;

use AmazonPHP\SellingPartner\Marketplace;
use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\Entity\ProductSaleChannel;
use App\Helper\MailService;
use App\Service\Amazon\AmzApi;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;

class AmazonOfferStatus
{
    /**@var EntityManager */
    protected $manager;


    public function __construct(
        ManagerRegistry $manager,
        protected LoggerInterface $logger,
        protected MailService $mailer,
        protected BusinessCentralAggregator $businessCentralAggregator,
        protected AmzApi $amzApi,

    ) {
        $this->manager = $manager->getManager();
    }



    public function checkAllProducts()
    {
       

        $products = $this->getAllSaleChannels();
        $nbProducts = count($products);
        $this->logger->info('Count  skus '.$nbProducts);
        foreach($products as $sku =>$saleChannels){
                $this->logger->info('Start sku '.$sku);
                foreach($saleChannels as $saleChannel){
                    $codeSaleChannel = $saleChannel->getSaleChannel()->getCode();
                    $this->logger->info('Checking sku '.$sku.' on '. $codeSaleChannel);
                      try {
                         $statusOnAmz = $this->amzApi->getListingForSku($this->getCorrespondanceSku($sku), $this->getEquivalence($codeSaleChannel));
                         if($statusOnAmz->getSummaries()){
                            $statutes = $statusOnAmz->getSummaries()[0]['status'];
                            
                            if(in_array("BUYABLE", $statutes)){
                                $saleChannel->setPublished(true);
                                $saleChannel->setReason(null);
                            } else {
                                $saleChannel->setPublished(false);
                                $saleChannel->setReason('No stock');
                            }


                         } else {
                            $saleChannel->setPublished(false);
                            $errors = [];
                            foreach($statusOnAmz->getIssues() as $issue){
                                if($issue->getSeverity() == "ERROR"){
                                    $errors[] =  $issue->getMessage();
                                }
                            }
                            $saleChannel->setReason(implode(" | ", $errors));
                         }
                    } catch (Exception $e){
                        $saleChannel->setPublished(false);
                        $this->logger->alert($e->getMessage());
                        $saleChannel->setReason($e->getMessage());
                    }                   

                   

                   
                    
                }  
                sleep(1);
                $nbProducts--;
                $this->logger->info('Still  skus '.$nbProducts);  
                if($nbProducts % 50 == 0){
                    sleep(5);
                    $this->manager->flush();
                }
        }
        $this->manager->flush();    
        $this->updateDisabledPutNull();
    }

    protected function getAllSaleChannelCode(){
        return [
            'amazon_fr_kp',
            'amazon_es_kp',
            'amazon_uk_kp',
            'amazon_it_kp',
            'amazon_de_kp',
        ];

    }


    public function getCorrespondanceSku($sku){

        $equivalence=[
            'PX-P3D2449' => 'P3D2449',
            'PX-P3D2450' => 'P3D2450',
            'PX-P3D2453' => 'P3D2453',
            'PX-P3D2454' => 'P3D2454',
            'PX-P3D2577' => 'P3D2577',
            'PX-P3D2456' => 'P3D2456',
            'PX-P2A1823' => 'P2A1823',
            'PX-P2A1016' => 'P2A1016',
            'PX-P2D2077' => 'P2D2077',
            'PX-P2D2076' => 'P2D2076',
        ];

        return array_key_exists($sku,$equivalence) ? $equivalence[$sku] : $sku;
        
    }



    public function getEquivalence($code)
    {
        $equivalence=[
            'amazon_es_kp' => Marketplace::fromCountry('ES')->id(),
            'amazon_fr_kp' => Marketplace::fromCountry('FR')->id(),
            'amazon_de_kp' => Marketplace::fromCountry('DE')->id(),
            'amazon_it_kp' => Marketplace::fromCountry('IT')->id(),
            'amazon_uk_kp' => Marketplace::fromCountry('GB')->id(),
        ];

        return $equivalence[$code];
    }

    protected function getAllSaleChannels(){
        $queryBuilder = $this->manager->createQueryBuilder();

        $queryBuilder->select('productsalechannel')
            ->from(ProductSaleChannel::class, 'productsalechannel')
            ->leftJoin('productsalechannel.saleChannel', 'salechannel')
            ->where('productsalechannel.enabled = 1')
            ->andWhere($queryBuilder->expr()->in('salechannel.code', $this->getAllSaleChannelCode()));

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

    protected function updateDisabledPutNull(){

        $subQueryBuilder = $this->manager->createQueryBuilder();
        $subQueryBuilder->select('productsalechannel.id')
                ->from(ProductSaleChannel::class, 'productsalechannel')
                ->leftJoin('productsalechannel.saleChannel', 'salechannel')
                ->where('productsalechannel.enabled = 0')
                ->andWhere($subQueryBuilder->expr()->isNotNull('productsalechannel.published'))
                ->andWhere($subQueryBuilder->expr()->in('salechannel.code', $this->getAllSaleChannelCode()));

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



    




}
