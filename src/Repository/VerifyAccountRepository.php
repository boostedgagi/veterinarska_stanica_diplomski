<?php

namespace App\Repository;

use App\Entity\VerifyAccount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VerifyAccount>
 *
 * @method VerifyAccount|null find($id, $lockMode = null, $lockVersion = null)
 * @method VerifyAccount|null findOneBy(array $criteria, array $orderBy = null)
 * @method VerifyAccount[]    findAll()
 * @method VerifyAccount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VerifyAccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VerifyAccount::class);
    }

    public function save(VerifyAccount $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(VerifyAccount $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findTokenByTokenValue(string $token):object
    {
        $qb = $this->createQueryBuilder('va');
        $qb->andWhere('va.token = :token')
            ->setParameter('token',$token);

        return (object)$qb->getQuery()->getResult();
    }

//    /**
//     * @return VerifyAccount[] Returns an array of VerifyAccount objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('v.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('v.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?VerifyAccount
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('v.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
