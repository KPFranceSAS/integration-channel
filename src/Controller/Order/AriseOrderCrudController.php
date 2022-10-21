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

class AriseOrderCrudController extends WebOrderCrudController
{
    public function getName(): string
    {
        return "Arise Order";
    }


    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('entity.channel IN (:channels)');
        $qb->setParameter(
            'channels',
            [
                IntegrationChannel::CHANNEL_AMAZFIT_ARISE,
                IntegrationChannel::CHANNEL_ARISE
            ]
        );
        return $qb;
    }


    public function getCompanies()
    {
        return  [
            BusinessCentralConnector::GADGET_IBERIA => BusinessCentralConnector::GADGET_IBERIA,
        ];
    }


    public function getChannels()
    {
        return  [
            IntegrationChannel::CHANNEL_AMAZFIT_ARISE => IntegrationChannel::CHANNEL_AMAZFIT_ARISE,
            IntegrationChannel::CHANNEL_ARISE => IntegrationChannel::CHANNEL_ARISE,
        ];
    }


    public function getMarketplaces()
    {
        return [
            'Arise' => 'Arise',
        ];
    }
}
