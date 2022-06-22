<?php

namespace App\Controller\Order;

use App\Controller\Order\WebOrderCrudController;
use App\Entity\WebOrder;
use App\Helper\BusinessCentral\Connector\BusinessCentralConnector;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;

class FitbitExpressOrderCrudController extends WebOrderCrudController
{
    public function getName(): string
    {
        return "Fitbit Order";
    }


    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('entity.channel = :channel');
        $qb->setParameter('channel', WebOrder::CHANNEL_FITBITEXPRESS);
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
            'AliExpress' => 'AliExpress',
        ];
    }
}
