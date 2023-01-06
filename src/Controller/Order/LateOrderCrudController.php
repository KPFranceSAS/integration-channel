<?php

namespace App\Controller\Order;

use App\Controller\Order\WebOrderCrudController;
use App\Filter\LateOrderFilter;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;

class LateOrderCrudController extends WebOrderCrudController
{
    public function getName(): string
    {
        return "Late order";
    }


    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $alias = current($qb->getRootAliases());
        LateOrderFilter::modifyQuery($qb, $alias);
        return $qb;
    }


    public function configureActions(Actions $actions): Actions
    {
        $actions =parent::configureActions($actions);
        $actions->remove(Crud::PAGE_INDEX, 'late');
        return $actions;
    }
}
