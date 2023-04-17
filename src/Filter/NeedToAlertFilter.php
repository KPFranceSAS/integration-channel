<?php

namespace App\Filter;

use App\Filter\BooleanFilterType;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;

class NeedToAlertFilter implements FilterInterface
{

    use FilterTrait;

    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(BooleanFilterType::class);
    }

    private $marketplace;

    public function setMarketplace(string $marketplace)
    {
        $this->marketplace = $marketplace;
        return $this;
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {

        if ($filterDataDto->getValue()) {
            $alias = $filterDataDto->getEntityAlias();

            $stock = $this->marketplace == 'Eu' ? 'laRocaBusinessCentralStock' : 'uk3plBusinessCentralStock';

            $queryBuilder->andWhere('(' . $alias . '.fba' . $this->marketplace . 'TotalStock + ' . $alias . '.fba' . $this->marketplace . 'InboundStock ) < ' . $alias . '.minQtyFba' . $this->marketplace)
                ->andWhere($alias . '.'.$stock.' > 0')
                ->andWhere($alias . '.minQtyFba' . $this->marketplace . ' > 0');
        }
    }
}
