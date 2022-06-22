<?php

namespace App\Controller\Order;

use App\Controller\Order\WebOrderCrudController;
use App\Entity\WebOrder;
use App\Helper\BusinessCentral\Connector\BusinessCentralConnector;
use DateInterval;
use DateTime;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;

class ChannelAdvisorOrderCrudController extends WebOrderCrudController
{
    public function getName(): string
    {
        return "Amazon Order";
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('entity.channel = :channel');
        $qb->setParameter('channel', WebOrder::CHANNEL_CHANNELADVISOR);
        return $qb;
    }


    public function getCompanies()
    {
        return  [
            BusinessCentralConnector::KP_FRANCE => BusinessCentralConnector::KP_FRANCE,
            BusinessCentralConnector::GADGET_IBERIA => BusinessCentralConnector::GADGET_IBERIA,
        ];
    }



    public function getMarketplaces()
    {
        return [
            'Amazon UK' => 'Amazon UK',
            'Amazon IT'  => "Amazon Seller Central - IT",
            'Amazon DE' => "Amazon Seller Central - DE",
            'Amazon ES' => "Amazon Seller Central - ES",
            'Amazon FR' => 'Amazon Seller Central - FR',
        ];
    }
}
