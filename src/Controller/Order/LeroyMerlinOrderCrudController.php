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

class LeroyMerlinOrderCrudController extends WebOrderCrudController
{
    public function getName(): string
    {
        return "Leroy Merlin Order";
    }


    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('entity.channel IN (:channels)');
        $qb->setParameter(
            'channels',
            [
                IntegrationChannel::CHANNEL_LEROYMERLIN,
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
            IntegrationChannel::CHANNEL_LEROYMERLIN => IntegrationChannel::CHANNEL_LEROYMERLIN,
        ];
    }


    public function getMarketplaces()
    {
        return [
            'Leroy Merlin FR' => "Leroy Merlin LMFR",
            'Leroy Merlin ES' => "Leroy Merlin LMES",
            'Leroy Merlin IT' => "Leroy Merlin LMIT",
        ];
    }
}
