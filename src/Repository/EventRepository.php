<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\User;
use App\Enum\EventStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * Build a Doctrine Query object for searching and filtering events.
     *
     * @param array $criteria Search/filter criteria
     */
    public function searchQuery(array $criteria): Query
    {
        $qb = $this->createQueryBuilder('e');

        if (!empty($criteria['query'])) {
            $qb->andWhere('e.title LIKE :query OR e.description LIKE :query')
                ->setParameter('query', '%'.$criteria['query'].'%');
        }

        if (!empty($criteria['level'])) {
            $qb->andWhere('e.requiredLevel = :level')
                ->setParameter('level', $criteria['level']);
        }

        if (!empty($criteria['status']) && 'all' !== $criteria['status']) {
            $qb->andWhere('e.status = :status')
                ->setParameter('status', $criteria['status']);
        } elseif (empty($criteria['status'])) {
            $qb->andWhere('e.status = :status')
                ->setParameter('status', EventStatus::PUBLISHED);
        }

        if (!empty($criteria['dateFrom'])) {
            $qb->andWhere('e.eventDate >= :dateFrom')
                ->setParameter('dateFrom', $criteria['dateFrom']);
        }

        if (!empty($criteria['dateTo'])) {
            $qb->andWhere('e.eventDate <= :dateTo')
                ->setParameter('dateTo', $criteria['dateTo']);
        }

        $sort = $criteria['sort'] ?? 'date_desc';

        switch ($sort) {
            case 'date_asc':
                $qb->orderBy('e.eventDate', 'ASC');
                break;
            case 'date_desc':
                $qb->orderBy('e.eventDate', 'DESC');
                break;
            case 'distance_asc':
                $qb->orderBy('e.distance', 'ASC');
                break;
            case 'distance_desc':
                $qb->orderBy('e.distance', 'DESC');
                break;
            default:
                $qb->orderBy('e.eventDate', 'DESC');
        }

        return $qb->getQuery();
    }

    /**
     * Convenience wrapper for legacy calls: returns results directly.
     */
    public function search(array $criteria): array
    {
        return $this->searchQuery($criteria)->getResult();
    }

    public function findOrganizedByUser(User $user): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.organizer = :user')
            ->setParameter('user', $user)
            ->orderBy('e.eventDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countOrganizedByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.organizer = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
