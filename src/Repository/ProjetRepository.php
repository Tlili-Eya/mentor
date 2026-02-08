<?php

namespace App\Repository;

use App\Entity\Projet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Projet>
 */
class ProjetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Projet::class);
    }

    //    /**
    //     * @return Projet[] Returns an array of Projet objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Projet
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
     public function findBySearchAndSort(?string $search = null, ?string $sort = null): array
    {
        $qb = $this->createQueryBuilder('p');
        
        // Gestion de la recherche
        if ($search) {
            $qb->andWhere('p.titre LIKE :search OR p.type LIKE :search OR p.technologies LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        // Gestion du tri
        switch ($sort) {
            case 'titre_asc':
                $qb->orderBy('p.titre', 'ASC');
                break;
            case 'titre_desc':
                $qb->orderBy('p.titre', 'DESC');
                break;
            case 'date_debut_asc':
                $qb->orderBy('p.dateDebut', 'ASC');
                break;
            case 'date_debut_desc':
                $qb->orderBy('p.dateDebut', 'DESC');
                break;
            case 'ressources_desc':
                // Tri par nombre de ressources décroissant
                $qb->leftJoin('p.ressources', 'r')
                   ->addSelect('COUNT(r.id) as HIDDEN nbRessources')
                   ->groupBy('p.id')
                   ->orderBy('nbRessources', 'DESC');
                break;
            case 'ressources_asc':
                // Tri par nombre de ressources croissant
                $qb->leftJoin('p.ressources', 'r')
                   ->addSelect('COUNT(r.id) as HIDDEN nbRessources')
                   ->groupBy('p.id')
                   ->orderBy('nbRessources', 'ASC');
                break;
            default:
                $qb->orderBy('p.id', 'DESC');
        }
        
        return $qb->getQuery()->getResult();
    }
}

