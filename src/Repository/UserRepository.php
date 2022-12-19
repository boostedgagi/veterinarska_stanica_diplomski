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

    public function getNearbyVets(string $latitude, string $longitude, int $distance): array
    {
        $rsm = new ResultSetMapping;
        $em = $this->getEntityManager();

        $rsm->addEntityResult(User::class, 'u');
        $rsm->addFieldResult('u', 'first_name', 'firstName');
        $rsm->addFieldResult('u', 'last_name', 'lastName');
        $rsm->addFieldResult('u', 'email', 'email');
        $rsm->addFieldResult('u', 'phone', 'phone');
        $rsm->addFieldResult('u', 'latitude', 'latitude');
        $rsm->addFieldResult('u', 'longitude', 'longitude');

        $sql = 'SELECT first_name, last_name, email, phone, latitude, longitude,(
        3959 *
        acos(
            cos(radians(?)) *
            cos(radians(latitude)) *
            cos(radians(longitude) - radians(?)) + sin(radians(?)) *
            sin(radians(latitude)))
        ) AS distance
        FROM user 
        where type_of_user = 2
        HAVING distance < ?
        ORDER BY distance';

        $query = $em->createNativeQuery($sql,$rsm);
//        $vet = 2;
        $query->setParameter(1, $latitude);
        $query->setParameter(2, $longitude);
        $query->setParameter(3, $latitude);
//        $query->setParameter(4,$vet);
        $query->setParameter(4, $distance);

        $vets = $query->getResult();

        return $vets;
    }

    /**
     * @throws Exception
     */
    public function getVets(): array
    {
        $em = $this->getEntityManager();
        $rsm = new ResultSetMapping();

        $conn = $em->getConnection();
        $query = 'SELECT first_name,last_name,email,phone 
                FROM user 
                WHERE type_of_user = :vet_type';
        $stmt = $conn->prepare($query);
        $vets = $stmt->execute(array('vet_type' => 2));

        return $vets->fetchAll();
    }

//    /**
//     * @return User[] Returns an array of User objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?User
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
