<?php
// src/Repository/ReferenceArticleRepository.php

namespace App\Repository;

use App\Entity\ReferenceArticle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReferenceArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReferenceArticle::class);
    }

    public function findPublishedArticles()
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.published = :published')
            ->setParameter('published', true)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // Recherche avec filtres
    public function searchArticles($search = null, $categorieId = null, $published = null)
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.categorie', 'c');

        if ($search) {
            $qb->andWhere('r.titre LIKE :search OR r.contenu LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($categorieId) {
            $qb->andWhere('c.id = :categorieId')
               ->setParameter('categorieId', $categorieId);
        }

        if ($published !== null) {
            $qb->andWhere('r.published = :published')
               ->setParameter('published', $published);
        }

        return $qb->orderBy('r.createdAt', 'DESC')
                 ->getQuery()
                 ->getResult();
    }
       public function countRecentArticles(int $days): int
    {
        $date = new \DateTime("-{$days} days");
        
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.published = :published')
            ->andWhere('a.createdAt >= :date')
            ->setParameter('published', true)
            ->setParameter('date', $date)
            ->getQuery()
            ->getSingleScalarResult();
    }
}