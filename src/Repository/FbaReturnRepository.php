<?php

namespace App\Repository;

use App\Entity\FbaReturn;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FbaReturn|null find($id, $lockMode = null, $lockVersion = null)
 * @method FbaReturn|null findOneBy(array $criteria, array $orderBy = null)
 * @method FbaReturn[]    findAll()
 * @method FbaReturn[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FbaReturnRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FbaReturn::class);
    }

    // /**
    //  * @return FbaReturn[] Returns an array of FbaReturn objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?FbaReturn
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
