<?php

namespace App\Repository;

use App\Entity\Parcours;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Parcours>
 */
class ParcoursRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Parcours::class);
    }

    //    /**
    //     * @return Parcours[] Returns an array of Parcours objects
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

    //    public function findOneBySomeField($value): ?Parcours
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
            $qb->andWhere('p.titre LIKE :search OR p.type_parcours LIKE :search OR p.diplome LIKE :search')
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
                $qb->orderBy('p.date_debut', 'ASC');
                break;
            case 'date_debut_desc':
                $qb->orderBy('p.date_debut', 'DESC');
                break;
            case 'type_asc':
                $qb->orderBy('p.type_parcours', 'ASC');
                break;
            case 'type_desc':
                $qb->orderBy('p.type_parcours', 'DESC');
                break;
            default:
                $qb->orderBy('p.id', 'DESC');
        }
        
        return $qb->getQuery()->getResult();
    }
}
