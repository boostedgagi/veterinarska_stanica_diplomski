<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;


/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);

        $this->save($user, true);
    }

    /**
     * @throws Exception
     */
    public function getNearbyVets(string $latitude, string $longitude, int $distance): array
    {
//        $rsm = new ResultSetMapping();
//
//        $rsm->addEntityResult(User::class,'u');
//
//        $rsm->addFieldResult('u','first_name','firstName');
//        $rsm->addFieldResult('u','last_name','lastName');
//        $rsm->addFieldResult('u','email','email');
//        $rsm->addFieldResult('u','phone','phone');
//        $rsm->addFieldResult('u','latitude','latitude');
//        $rsm->addFieldResult('u','longitude','longitude');

        $em = $this->getEntityManager();
        $km_constant = 6371;
        $sql = "SELECT first_name,last_name,email,phone,latitude,longitude,round(
        (
            $km_constant * ACOS(COS(RADIANS(:latitude)) 
            * COS(RADIANS(latitude)) * 
            COS(RADIANS(longitude) - 
            RADIANS(:longitude)) + 
            SIN(RADIANS(:latitude)) * 
            SIN(RADIANS(latitude)))
            )
        ,2
        ) AS distance
        FROM
            user
        WHERE
            type_of_user = 2
        HAVING distance < :dist
        ORDER BY distance
        LIMIT 0 , 5";

        $conn = $em->getConnection();
        $stmt = $conn->prepare($sql);

        $stmt->bindValue('latitude', $latitude);
        $stmt->bindValue('longitude', $longitude);
        $stmt->bindValue('dist', $distance);

        $nearbyVets = $stmt->execute();

        //returns all from select clause with rounded distance by two decimal places
        return $nearbyVets->fetchAll();
    }

    public function getOccupiedVetsInTimeRange($from,$to):array
    {
        $qb = $this->createQueryBuilder('u');

        $qb->innerJoin('u.healthRecords','hr')
//            ->addSelect('u.id')
            ->orWhere('hr.startedAt >= :from and hr.startedAt <= :to')
            ->orWhere('hr.finishedAt >= :from and hr.finishedAt <= :to')
            ->orWhere('u.id not in (hr.vet)')
            ->setParameter('from',$from)
            ->setParameter('to',$to);

        return $qb->getQuery()->getResult();
    }

    public function excludeOccupiedVets(array $occupiedVets):array
    {
        $occupiedVetsIDs = [];
        
        foreach($occupiedVets as $occupiedVet){
            $occupiedVetsIDs[] = $occupiedVet->getId();
        }

        $qb = $this->createQueryBuilder('u');
        $qb->select('u')
            ->andWhere('u.id not in (:occupiedVetsIDs)')
            ->setParameter('occupiedVetsIDs',$occupiedVetsIDs)
            ->andWhere('u.typeOfUser=2')
            ->orderBy('u.id');

        return $qb->getQuery()->getResult();
    }

    public function getFreeVets($from,$to):array
    {
        $occupiedVets = $this->getOccupiedVetsInTimeRange($from,$to);
        return $this->excludeOccupiedVets($occupiedVets);
    }

}
