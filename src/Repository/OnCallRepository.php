<?php

namespace App\Repository;

use App\Entity\OnCall;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OnCall>
 *
 * @method OnCall|null find($id, $lockMode = null, $lockVersion = null)
 * @method OnCall|null findOneBy(array $criteria, array $orderBy = null)
 * @method OnCall[]    findAll()
 * @method OnCall[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OnCallRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OnCall::class);
    }

    public function save(OnCall $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(OnCall $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return OnCall[] Returns an array of OnCall objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('o.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?OnCall
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }


    public function getActiveVets():?array
    {
        $qb = $this->createQueryBuilder('o');

        $last24hours = new DateTime('- 1 day');
        $now = new DateTime();

        $qb
            ->select('vet.id','vet.email','vet.firstName','vet.lastName')
            ->innerJoin('o.vet','vet')
            ->andWhere('o.startedAt between :last24hours and :now')
            ->setParameter('last24hours',$last24hours)
            ->setParameter('now',$now)
            ->andWhere('o.finishedAt is NULL')
            ->orderBy('RAND()');

        return $qb->getQuery()->getResult();
    }
}
