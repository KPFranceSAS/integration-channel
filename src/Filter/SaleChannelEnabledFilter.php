<?php

namespace App\Filter;

use App\Entity\Product;
use App\Entity\ProductSaleChannel;
use App\Entity\SaleChannel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\EntityFilterType;

class SaleChannelEnabledFilter implements FilterInterface
{
    use FilterTrait;


    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setFilterFqcn(self::class)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(EntityFilterType::class)
            ->setFormTypeOption('translation_domain', 'EasyAdminBundle')
            ->setFormTypeOption('value_type_options.class', SaleChannel::class)
            
            ->setFormTypeOption('value_type_options.multiple', false);

    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        $alias = $filterDataDto->getEntityAlias();

        $comparaison = $filterDataDto->getComparison();
        $value = $filterDataDto->getValue();
        if (null !== $value) {
            $subQueryBuilder = $queryBuilder->getEntityManager()->createQueryBuilder();
            $subQueryBuilder->select('pscd.id')
                    ->from(ProductSaleChannel::class, 'psc')
                    ->leftJoin('psc.product', 'pscd')
                    ->leftJoin('psc.saleChannel', 'pscc')
                    ->where('pscc.id = :saleChannelId')
                    ->andWhere('psc.enabled = 1');
            if($comparaison=="!="){
                $queryBuilder->andWhere($queryBuilder->expr()->notIn($alias.'.id', $subQueryBuilder->getDQL()))->setParameter('saleChannelId', $value->getId());
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->in($alias.'.id', $subQueryBuilder->getDQL()))->setParameter('saleChannelId', $value->getId());
            }
        }
    }


   
     
}
