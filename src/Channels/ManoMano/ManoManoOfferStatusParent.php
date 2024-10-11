<?php

namespace App\Channels\ManoMano;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\Channels\ManoMano\ManoManoApiParent;
use App\Entity\ProductSaleChannel;
use App\Helper\MailService;
use App\Service\Aggregator\ApiAggregator;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

abstract class ManoManoOfferStatusParent
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


    protected function getManoManoApi(): ManoManoApiParent
    {
        return $this->apiAggregator->getApi($this->getChannel());
    }


    public function checkAllProducts()
    {

       
        $managed = [];

        $products = $this->getAllSaleChannels($this->getChannel());
        $offerManomanos = $this->getManoManoApi()->getAllOffers();
        $this->logger->info(count($offerManomanos).' offers');
        foreach ($offerManomanos as $offerManomano) {
            if (array_key_exists($offerManomano["sku"], $products)) {
               
                $published = $offerManomano["offer_is_online"];
                $reason = null;
                if ($published==false) {
                    if (count($offerManomano['errors'])>0) {
                        $reason = implode(',', $offerManomano['errors']);
                    } elseif ($offerManomano['stock']==0) {
                        $reason = 'Out of stock';
                    } else {
                        $reason = 'Unknown';
                    }
                }
                

                $productChannel = $products[$offerManomano["sku"]];
                $productChannel->setPublished($published);
                $productChannel->setReason($reason);

                $this->logger->info('offer '.$offerManomano["sku"].'>> '.$published ? 'Active ' : 'Inactive ('.$reason.')');
                $managed[]=$offerManomano["sku"];
            }
        }

        foreach ($products as $sku => $productChannel) {
            if (!in_array($sku, $managed)) {
                $productChannel->setPublished($published);
                $productChannel->setReason('Check import status error logs https://toolbox.manomano.com/catalog/import/logs');
            }
        }

        
        $this->manager->flush();
        $this->updateDisabledPutNull($this->getChannel());


        
    }



    protected function getAllSaleChannels($code)
    {
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
        foreach ($productSalesChannels as $productSalesChannel) {
            $productSku = $productSalesChannel->getProduct()->getSku();
            $products[$productSku]=$productSalesChannel;
        }

        return $products;
    }

    protected function updateDisabledPutNull($code)
    {

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

        if (count($ids)>0) {
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
