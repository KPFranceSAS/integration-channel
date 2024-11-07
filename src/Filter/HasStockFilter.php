<?php

namespace App\Filter;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\BooleanFilterType;

class HasStockFilter implements FilterInterface {

    
    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        // On s'assure que la valeur est un booléen
        if ($filterDataDto->getValue() === true) {
            $queryBuilder->andWhere('entity_product.laRocaBusinessCentralStock > 0');
        } elseif ($filterDataDto->getValue()  === false) {
            $queryBuilder->andWhere('entity_product.laRocaBusinessCentralStock <= 0 OR entity_product.laRocaBusinessCentralStock IS NULL');
        }
    }

        use FilterTrait;
    
        public static function new(string $propertyName, $label = null): self
        {
            return (new self())
                ->setFilterFqcn(__CLASS__)
                ->setProperty($propertyName)
                ->setLabel($label)
                ->setFormType(BooleanFilterType::class)
                ->setFormTypeOption('translation_domain', 'EasyAdminBundle');
        }
    
    }