<?php

namespace App\Controller\Order;

use App\Controller\Order\WebOrderCrudController;
use App\Entity\WebOrder;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;

class ErrorOrderCrudController extends WebOrderCrudController
{
    public function getName(): string
    {
        return "Error Order";
    }


    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('entity.status IN (:statusError)');
        $qb->setParameter('statusError', [WebOrder::STATE_ERROR, WebOrder::STATE_ERROR_INVOICE]);
        return $qb;
    }
}
