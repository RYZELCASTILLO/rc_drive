<?php

namespace App\Repository;

use App\Entity\Booking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    /**
     * Search by customer, phone or vehicle, and/or filter by status. Newest first.
     *
     * @return Booking[]
     */
    public function search(?string $term, ?string $status): array
    {
        $qb = $this->createQueryBuilder('b')
            ->leftJoin('b.vehicle', 'v')->addSelect('v')
            ->leftJoin('b.createdBy', 'u')->addSelect('u')
            ->orderBy('b.id', 'DESC');

        if ($term !== null && $term !== '') {
            $qb->andWhere($qb->expr()->orX(
                'b.customerName LIKE :term',
                'b.phone LIKE :term',
                'v.name LIKE :term'
            ))->setParameter('term', '%' . $term . '%');
        }

        if ($status !== null && $status !== '') {
            $qb->andWhere('b.status = :status')->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }
}