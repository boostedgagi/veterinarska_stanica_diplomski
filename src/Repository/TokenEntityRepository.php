<?php

namespace App\Repository;

use App\Entity\Token;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Token>
 *
 * @method Token|null find($id, $lockMode = null, $lockVersion = null)
 * @method Token|null findOneBy(array $criteria, array $orderBy = null)
 * @method Token[]    findAll()
 * @method Token[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TokenEntityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Token::class);
    }

    public function save(Token $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Token $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findTokenByTokenValue(string $token):array
    {
        $qb = $this->createQueryBuilder('va');
        $qb->addSelect('va.token as token')
            ->addSelect('va.expires as expires')
            ->andWhere('va.token = :token')
            ->setParameter('token',$token);

        return $qb->getQuery()->getResult();
    }

    public function getExpiredTokens():array
    {
        $now = strtotime(date('Y-m-d h:i:s'));
        dump($now);
        $qb = $this->createQueryBuilder('token');
        $qb->select('token')
            ->andWhere('token.expires < :now')
            ->setParameter('now',$now);
        dump($qb->getQuery()->getResult());
        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return Token[] Returns an array of Token objects
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

//    public function findOneBySomeField($value): ?Token
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('v.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
