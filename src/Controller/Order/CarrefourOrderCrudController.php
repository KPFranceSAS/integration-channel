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

class CarrefourOrderCrudController extends WebOrderCrudController
{
    public function getName(): string
    {
        return "Carrefour Order";
    }


    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('entity.channel IN (:channels)');
        $qb->setParameter(
            'channels',
            [
                IntegrationChannel::CHANNEL_CARREFOUR_ES,
            ]
        );
        return $qb;
    }


    public function getCompanies()
    {
        return  [
            BusinessCentralConnector::KIT_PERSONALIZACION_SPORT => BusinessCentralConnector::KIT_PERSONALIZACION_SPORT,
        ];
    }


    public function getChannels()
    {
        return  [
            IntegrationChannel::CHANNEL_CARREFOUR_ES => IntegrationChannel::CHANNEL_CARREFOUR_ES,
        ];
    }


    public function getMarketplaces()
    {
        return [
            'Carrefour ES' => 'Carrefour ES',
        ];
    }
}
