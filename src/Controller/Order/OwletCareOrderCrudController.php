<?php

namespace App\Controller\Order;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Controller\Order\WebOrderCrudController;
use App\Entity\IntegrationChannel;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;

class OwletCareOrderCrudController extends WebOrderCrudController
{
    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        $qb = $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('entity.channel = :channel');
        $qb->setParameter('channel', IntegrationChannel::CHANNEL_OWLETCARE);
        return $qb;
    }

    public function getName(): string
    {
        return "OwletCare Order";
    }
    


    public function getCompanies()
    {
        return  [
            BusinessCentralConnector::KIT_PERSONALIZACION_SPORT => BusinessCentralConnector::KIT_PERSONALIZACION_SPORT,
        ];
    }


    public function getMarketplaces()
    {
        return [
            'Owletbaby.es' => 'Owletbaby.es',
        ];
    }


    public function getChannels()
    {
        return  [
            IntegrationChannel::CHANNEL_OWLETCARE => IntegrationChannel::CHANNEL_OWLETCARE,
        ];
    }
}
