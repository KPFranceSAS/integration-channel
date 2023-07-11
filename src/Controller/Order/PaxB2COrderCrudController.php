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

class PaxB2COrderCrudController extends WebOrderCrudController
{
    public function getName(): string
    {
        return "Pax B2C Order";
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('entity.channel IN (:channels)');
        $qb->setParameter(
            'channels',
            [
                IntegrationChannel::CHANNEL_PAXEU,
                IntegrationChannel::CHANNEL_PAXUK,
            ]
        );
        return $qb;
    }


    public function getCompanies()
    {
        return  [
            BusinessCentralConnector::KP_UK => BusinessCentralConnector::KP_UK,
            BusinessCentralConnector::KP_FRANCE => BusinessCentralConnector::KP_FRANCE,
        ];
    }


    public function getMarketplaces()
    {
        return [
            'Uk.pax.com' => 'Uk.pax.com',
            'Eu.pax.com' => 'Eu.pax.com',
        ];
    }

    public function getChannels()
    {
        return  [
            IntegrationChannel::CHANNEL_PAXUK => IntegrationChannel::CHANNEL_PAXUK,
            IntegrationChannel::CHANNEL_PAXEU => IntegrationChannel::CHANNEL_PAXEU,
        ];
    }
}
