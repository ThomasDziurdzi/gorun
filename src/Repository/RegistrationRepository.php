<?php

namespace App\Repository;

use App\Entity\Registration;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Registration>
 */
class RegistrationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Registration::class);
    }

    public function findUpcomingByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.event', 'e')
            ->where('r.user = :user')
            ->andWhere('r.status = :status')
            ->andWhere('e.eventDate >= :now')
            ->setParameter('user', $user)
            ->setParameter('status', 'CONFIRMED')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('e.eventDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

     public function findPastByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.event', 'e')
            ->where('r.user = :user')
            ->andWhere('r.status = :status')
            ->andWhere('e.eventDate < :now')
            ->setParameter('user', $user)
            ->setParameter('status', 'CONFIRMED')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('e.eventDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

     public function countConfirmedByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.user = :user')
            ->andWhere('r.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'CONFIRMED')
            ->getQuery()
            ->getSingleScalarResult();
    }

     public function getTotalKilometersByUser(User $user): float
    {
        $result = $this->createQueryBuilder('r')
            ->select('SUM(e.distance)')
            ->join('r.event', 'e')
            ->where('r.user = :user')
            ->andWhere('r.status = :status')
            ->andWhere('e.eventDate < :now')
            ->setParameter('user', $user)
            ->setParameter('status', 'CONFIRMED')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }
}
