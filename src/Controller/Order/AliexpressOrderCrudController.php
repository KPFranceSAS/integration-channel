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

class AliexpressOrderCrudController extends WebOrderCrudController
{
    public function getName(): string
    {
        return "Aliexpress Order";
    }


    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('entity.channel IN (:channels)');
        $qb->setParameter(
            'channels',
            [
                IntegrationChannel::CHANNEL_ALIEXPRESS,
                IntegrationChannel::CHANNEL_FITBITEXPRESS
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
            IntegrationChannel::CHANNEL_ALIEXPRESS => IntegrationChannel::CHANNEL_ALIEXPRESS,
            IntegrationChannel::CHANNEL_FITBITEXPRESS => IntegrationChannel::CHANNEL_FITBITEXPRESS,
        ];
    }


    public function getMarketplaces()
    {
        return [
            'AliExpress' => 'AliExpress',
        ];
    }
}
