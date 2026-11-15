<?php

namespace App\Repository;

use App\Entity\Inquiry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Inquiry>
 */
class InquiryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Inquiry::class);
    }

    /**
     * All inquiries with their messages, the most recently active first.
     *
     * @return Inquiry[]
     */
    public function findInbox(): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.messages', 'm')->addSelect('m')
            ->orderBy('i.updatedAt', 'DESC')
            ->addOrderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}