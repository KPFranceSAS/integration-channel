<?php

namespace App\Repository;

use App\Entity\Job;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Job>
 *
 * @method Job|null find($id, $lockMode = null, $lockVersion = null)
 * @method Job|null findOneBy(array $criteria, array $orderBy = null)
 * @method Job[]    findAll()
 * @method Job[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class JobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Job::class);
    }

    /**
     * @return Job[] Returns an array of Job objects
     */
    public function findAllProcessingChannel($jobType, $channel): array
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.jobType = :jobType')
            ->andWhere('j.channel = :channel')
            ->andWhere('j.status IN (0,1)')
            ->setParameter('jobType', $jobType)
            ->setParameter('channel', $channel)
            ->orderBy('j.id', 'ASC')
           ->getQuery()
            ->getResult()
       ;
    }

}
