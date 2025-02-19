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

class MediaMarktOrderCrudController extends WebOrderCrudController
{
    public function getName(): string
    {
        return "MediaMarkt Order";
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('entity.channel = :channel');
        $qb->setParameter('channel', IntegrationChannel::CHANNEL_MEDIAMARKT);
        return $qb;
    }


    public function getCompanies()
    {
        return  [
            BusinessCentralConnector::GADGET_IBERIA => BusinessCentralConnector::GADGET_IBERIA,
        ];
    }


    public function getMarketplaces()
    {
        return [
            'Mediamarkt.es' => 'Mediamarkt.es',
        ];
    }

    public function getChannels()
    {
        return  [
            IntegrationChannel::CHANNEL_MEDIAMARKT => IntegrationChannel::CHANNEL_MEDIAMARKT,
        ];
    }
}
