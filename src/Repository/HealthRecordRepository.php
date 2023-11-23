<?php

namespace App\Repository;

use App\Entity\HealthRecord;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;

/**
 * @extends ServiceEntityRepository<HealthRecord>
 *
 * @method HealthRecord|null find($id, $lockMode = null, $lockVersion = null)
 * @method HealthRecord|null findOneBy(array $criteria, array $orderBy = null)
 * @method HealthRecord[]    findAll()
 * @method HealthRecord[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HealthRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HealthRecord::class);
    }

    public function save(HealthRecord $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(HealthRecord $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function examinationsCount(): int
    {
        $qb = $this->createQueryBuilder('hr');
        $qb->select('count(hr.id) numberOfExaminations');

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getExaminationsInTimeRange(string $range): array
    {
        $now = new DateTime();
        $deadline = $this->timeRange($range);
        if(!$deadline)
        {
            return [];
        }
        $qb = $this->createQueryBuilder('hr');
        $qb->select('hr')
            ->andWhere('hr.startedAt>=:now')
            ->setParameter('now', $now)
            ->andWhere('hr.finishedAt<:deadline')
            ->setParameter('deadline', $deadline)
            ->andWhere('hr.notifiedWeekBefore = 0');

        return $qb->getQuery()->getResult();
    }

    private function timeRange(string $range): ?DateTime
    {
        if ($range === 'today')
        {
            return new DateTime('+1 day');
        }
        if ($range === 'next week')
        {
            return new DateTime('+7 days');
        }
        return null;
    }

    public function getLastMonthHealthRecords(int $numericalLastMonth): array
    {
        $from = new DateTime('- 30 days');
        $to = new DateTime();

        $qb = $this->createQueryBuilder('hr');
        $qb
            ->select(
                'hr.id id',
                'vet.firstName vetFirstName',
                'pet.name petName',
                'exam.name examName',
                'hr.startedAt',
                'hr.finishedAt',
                'hr.notifiedWeekBefore',
                'hr.notifiedDayBefore',
                'hr.madeByVet'
            )
            ->innerJoin('hr.vet', 'vet')
            ->innerJoin('hr.examination', 'exam')
            ->innerJoin('hr.pet', 'pet')
            ->andWhere('hr.startedAt>:from')
            ->setParameter('from', $from->format('Y-m-d'))
            ->andWhere('hr.finishedAt<:to')
            ->setParameter('to', $to->format('Y-m-d'))
            ->orderBy('hr.startedAt', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function findAllHealthRecordsForUser(User $user): array
    {
        $qb = $this->createQueryBuilder('hr');
        $qb
            ->innerJoin('hr.pet', 'p')
            ->innerJoin('p.owner', 'u')
            ->andWhere('u.id=:id')
            ->setParameter('id', $user->getId());

        return $qb->getQuery()->getResult();
    }
}
