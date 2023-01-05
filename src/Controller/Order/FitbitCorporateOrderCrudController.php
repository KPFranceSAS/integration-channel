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

class FitbitCorporateOrderCrudController extends WebOrderCrudController
{
    public function getName(): string
    {
        return "Fitbit Corporate Order";
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('entity.channel = :channel');
        $qb->setParameter('channel', IntegrationChannel::CHANNEL_FITBITCORPORATE);
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
            IntegrationChannel::CHANNEL_FITBITCORPORATE => IntegrationChannel::CHANNEL_FITBITCORPORATE,
        ];
    }


    public function getMarketplaces()
    {
        return [
            'Fitbitcorporate.kps.tech' => 'Fitbitcorporate.kps.tech',
        ];
    }
}
