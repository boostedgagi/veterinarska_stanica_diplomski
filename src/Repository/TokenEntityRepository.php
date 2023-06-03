<?php

namespace App\Repository;

use App\Entity\Token;
use App\Model\SavedToken;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Http\Discovery\Exception\NotFoundException;

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

    public function findTokenByTokenValue(string $token):SavedToken
    {
        $qb = $this->createQueryBuilder('va');
        $qb
            ->select('va.token token,va.expires expires')
            ->andWhere('va.token = :token')
            ->setParameter('token',$token);
        if(!$qb->getQuery()->getResult()){
            exit('Token not found.');
        }
        return $this->hydrate($qb->getQuery()->getResult());
    }

    public function getExpiredTokens():array
    {
        $now = strtotime(date('Y-m-d h:i:s'));
        $qb = $this->createQueryBuilder('token');
        $qb->select('token')
            ->andWhere('token.expires < :now')
            ->setParameter('now',$now);

        return $qb->getQuery()->getResult();
    }

    private function hydrate(array $results):SavedToken
    {
        $savedToken = new SavedToken();
        foreach($results[0] as $key=>$value){
            $savedToken->resolveAndFillVariable($key,$value);
        }
        return $savedToken;
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
