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

class ManoManoOrderCrudController extends WebOrderCrudController
{
    public function getName(): string
    {
        return "ManoMano Order";
    }


    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('entity.channel IN (:channels)');
        $qb->setParameter(
            'channels',
            [
                IntegrationChannel::CHANNEL_MANOMANO_DE,
                IntegrationChannel::CHANNEL_MANOMANO_FR,
                IntegrationChannel::CHANNEL_MANOMANO_ES,
                IntegrationChannel::CHANNEL_MANOMANO_IT,
            ]
        );
        return $qb;
    }


    public function getCompanies()
    {
        return  [
            BusinessCentralConnector::KP_FRANCE => BusinessCentralConnector::KP_FRANCE,
        ];
    }


    public function getChannels()
    {
        return  [
            IntegrationChannel::CHANNEL_MANOMANO_DE => IntegrationChannel::CHANNEL_MANOMANO_DE,
            IntegrationChannel::CHANNEL_MANOMANO_FR => IntegrationChannel::CHANNEL_MANOMANO_FR,
            IntegrationChannel::CHANNEL_MANOMANO_IT => IntegrationChannel::CHANNEL_MANOMANO_IT,
            IntegrationChannel::CHANNEL_MANOMANO_ES => IntegrationChannel::CHANNEL_MANOMANO_ES,
        ];
    }


    public function getMarketplaces()
    {
        return [
            'ManoMano FR' => "ManoMano FR",
            'ManoMano ES' => "ManoMano ES",
            'ManoMano DE' => "ManoMano DE",
            'ManoMano IT' => "ManoMano IT",
        ];
    }
}
