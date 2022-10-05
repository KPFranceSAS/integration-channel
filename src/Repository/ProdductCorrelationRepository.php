<?php

namespace App\Repository;

use App\Entity\ProdductCorrelation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProdductCorrelation>
 *
 * @method ProdductCorrelation|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProdductCorrelation|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProdductCorrelation[]    findAll()
 * @method ProdductCorrelation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProdductCorrelationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProdductCorrelation::class);
    }

    public function add(ProdductCorrelation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ProdductCorrelation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return ProdductCorrelation[] Returns an array of ProdductCorrelation objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ProdductCorrelation
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
