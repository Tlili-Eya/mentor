<?php

namespace App\Repository;

use App\Entity\Objectif;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Objectif>
 */
class ObjectifRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Objectif::class);
    }

    /**
     * Exemple de méthode personnalisée (optionnelle)
     * @return Objectif[] Returns an array of Objectif objects
     */
    public function findRecent(): array
    {
        return $this->createQueryBuilder('o')
            ->orderBy('o.dateDepot', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }
}