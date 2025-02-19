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

class FlashledOrderCrudController extends WebOrderCrudController
{
    public function getName(): string
    {
        return "Flashled Order";
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('entity.channel = :channel');
        $qb->setParameter('channel', IntegrationChannel::CHANNEL_FLASHLED);
        return $qb;
    }


    public function getCompanies()
    {
        return  [
            BusinessCentralConnector::KIT_PERSONALIZACION_SPORT => BusinessCentralConnector::KIT_PERSONALIZACION_SPORT,
            BusinessCentralConnector::TURISPORT => BusinessCentralConnector::TURISPORT,
        ];
    }

    public function getChannels()
    {
        return  [
            IntegrationChannel::CHANNEL_FLASHLED => IntegrationChannel::CHANNEL_FLASHLED,
        ];
    }


    public function getMarketplaces()
    {
        return [
            'Flashled.es' => 'Flashled.es',
        ];
    }
}
