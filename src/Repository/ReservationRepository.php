<?php

namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function findByCampusAndDate($campus, \DateTime $date)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.campus = :campus')
            ->andWhere('r.date_reservation = :date')
            ->setParameter('campus', $campus)
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }
}
