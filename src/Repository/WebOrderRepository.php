<?php

namespace App\Repository;

use App\Entity\WebOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WebOrder|null find($id, $lockMode = null, $lockVersion = null)
 * @method WebOrder|null findOneBy(array $criteria, array $orderBy = null)
 * @method WebOrder[]    findAll()
 * @method WebOrder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WebOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WebOrder::class);
    }
}
