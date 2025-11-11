<?php

namespace App\Repository;

use App\Entity\User;
use App\Model\VetAvailability;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use function get_class;


/**
 * @extends BaseRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends BaseRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
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
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        $user->setPassword($newHashedPassword);

        $this->save($user, true);
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findUserIdByMail(string $email): array
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u.id as user_id')
            ->andWhere('u.email=:email')
            ->setParameter('email', $email);

        return $qb->getQuery()->getResult();
    }

    /**
     * @throws Exception
     */
    public function getNearbyVets(string $latitude, string $longitude, int $distance): array
    {
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

        //returns all from select clause with distance rounded by two decimal places
        return $nearbyVets->fetchAll();
    }

    /**
     * @param $from
     * @param $to
     * @return array
     *
     * @see This method returns only occupied vets
     */
    public function getAvailableVets($from, $to): array
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.healthRecords', 'hr', 'WITH',
                'hr.vet = u AND hr.startedAt BETWEEN :from AND :to OR hr.finishedAt BETWEEN :from AND :to'
            )
            ->where('u.typeOfUser = :type')
            ->andWhere('hr.id IS NULL')
            ->setParameter('type', User::TYPE_VET)
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        return  $qb->getQuery()->getResult();
    }


    public function allVets(): array
    {
        $qb = $this->createQueryBuilder('u');

        $qb
            ->andWhere('u.typeOfUser=2')
            ->orderBy('u.popularity', 'desc');

        return $qb->getQuery()->getResult();
    }

    public function findByRole($role): User|int
    {
        $qb = $this->createQueryBuilder('u');

        $qb
            ->andWhere($qb->expr()->like('u.roles', ':role'))
            ->setParameter('role', '%' . $role . '%')
            ->orderBy('u.firstName', 'ASC');

        return $qb->getQuery()->getResult()[0];
    }

    /**
     * @param $firstName
     * @param $lastName
     * @return array []
     */
    public function findByFirstAndLastName($firstName, $lastName): array
    {
        if ($firstName || $lastName) {
            $qb = $this->createQueryBuilder('u');

            $qb->orWhere('u.firstName like :firstName')
                ->setParameter('firstName', '%' . $firstName . '%')
                ->orWhere('u.lastName like :lastName')
                ->setParameter('lastName', '%' . $lastName . '%')
                ->andWhere('u.typeOfUser=:userType')
                ->setParameter('userType', User::TYPE_USER);

            return $qb->getQuery()->getResult();
        }
        return [];
    }


    public function getId(string $email)
    {
        $qb = $this->createQueryBuilder('u');
        $qb
            ->select('u.id')
            ->andWhere('u.email=:email')
            ->setParameter('email', $email);

        return $qb->getQuery()->getResult();
    }
}
