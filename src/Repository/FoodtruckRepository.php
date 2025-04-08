<?php

namespace App\Repository;

use App\Entity\Foodtruck;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FoodtruckRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Foodtruck::class);
    }

    public function findByName(string $name)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.name LIKE :name')
            ->setParameter('name', '%'.$name.'%')
            ->getQuery()
            ->getResult();
    }
}
