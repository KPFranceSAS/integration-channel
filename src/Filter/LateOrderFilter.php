<?php

namespace App\Filter;

use App\Entity\WebOrder;
use App\Filter\BooleanFilterType;
use App\Helper\Utils\DatetimeUtils;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;

class LateOrderFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setFilterFqcn(self::class)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(BooleanFilterType::class);
    }


    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        if ($filterDataDto->getValue()) {
            $alias = $filterDataDto->getEntityAlias();
            self::modifyQuery($queryBuilder, $alias);
        }
    }


    public static function modifyQuery(QueryBuilder $queryBuilder, $alias)
    {
        $dateTimeDelivery = DatetimeUtils::getDateOutOfDelayBusinessDaysFrom(WebOrder::TIMING_DELIVERY)->format('Y-m-d H:i:s');
        $dateTimeShipping = DatetimeUtils::getDateOutOfDelayBusinessDaysFrom(WebOrder::TIMING_SHIPPING)->format('Y-m-d H:i:s');
        $dateTimeInvoice = DatetimeUtils::getDateOutOfDelay(WebOrder::TIMING_INTEGRATION)->format('Y-m-d H:i:s');

       
        $queryBuilder->andWhere(
            $queryBuilder->expr()->orX(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq($alias . '.fulfilledBy', ':fulfilledExternal'),
                    $queryBuilder->expr()->lt($alias . '.createdAt', ':dateTimeInvoice'),
                    $queryBuilder->expr()->eq($alias . '.status', ':statusSync')
                ),
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq($alias . '.fulfilledBy', ':fulfilledInternal'),
                    $queryBuilder->expr()->lt($alias . '.purchaseDate', ':dateTimeShipping'),
                    $queryBuilder->expr()->eq($alias . '.status', ':statusSync')
                ),
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq($alias . '.fulfilledBy', ':fulfilledInternal'),
                    $queryBuilder->expr()->lt($alias . '.purchaseDate', ':dateTimeDelivery'),
                    $queryBuilder->expr()->eq($alias . '.status', ':statusInvoiced')
                ),
            )
        )
            ->setParameters([
                'statusSync' => WebOrder::STATE_SYNC_TO_ERP,
                'statusInvoiced' => WebOrder::STATE_INVOICED,
                'dateTimeDelivery' => $dateTimeDelivery,
                'dateTimeShipping' => $dateTimeShipping,
                'dateTimeInvoice' => $dateTimeInvoice,
                'fulfilledExternal' => WebOrder::FULFILLED_BY_EXTERNAL,
                'fulfilledInternal' => WebOrder::FULFILLED_BY_SELLER,
            ]);
        return $queryBuilder;
    }
}
