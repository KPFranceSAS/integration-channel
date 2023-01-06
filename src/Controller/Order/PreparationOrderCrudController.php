<?php

namespace App\Controller\Order;

use App\Controller\Order\WebOrderCrudController;
use App\Entity\WebOrder;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;

class PreparationOrderCrudController extends WebOrderCrudController
{
    public function getName(): string
    {
        return "Waiting for shipping order";
    }


    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('entity.status IN (:statusDelivery)');
        $qb->andWhere('entity.fulfilledBy IN (:fulfilledByState)');
        $qb->setParameter('statusDelivery', [WebOrder::STATE_SYNC_TO_ERP]);
        $qb->setParameter('fulfilledByState', [WebOrder::FULFILLED_BY_SELLER]);
        return $qb;
    }
}
