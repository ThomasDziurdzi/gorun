<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
     * Search and filter events.
     *
     * @param array $criteria search/filter criterias
     *
     * @return Event[]
     */
    public function search(array $criteria): array
    {
        $qb = $this->createQueryBuilder('e')
            ->orderBy('e.eventDate', 'DESC');

        if (!empty($criteria['query'])) {
            $qb->andWhere('e.title LIKE :query OR e.description LIKE :query')
               ->setParameter('query', '%'.$criteria['query'].'%');
        }

        if (!empty($criteria['level'])) {
            $qb->andWhere('e.requiredLevel = :level')
               ->setParameter('level', $criteria['level']);
        }

        if (!empty($criteria['status'])) {
            $qb->andWhere('e.status = :status')
               ->setParameter('status', $criteria['status']);
        }

        if (!empty($criteria['dateFrom'])) {
            $qb->andWhere('e.eventDate >= :dateFrom')
               ->setParameter('dateFrom', $criteria['dateFrom']);
        }

        if (!empty($criteria['dateTo'])) {
            $qb->andWhere('e.eventDate <= :dateTo')
               ->setParameter('dateTo', $criteria['dateTo']);
        }

        return $qb->getQuery()->getResult();
    }
}
