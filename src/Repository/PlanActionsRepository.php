<?php
// src/Repository/PlanActionsRepository.php

namespace App\Repository;

use App\Entity\PlanActions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlanActions>
 */
class PlanActionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlanActions::class);
    }

    /**
     * Récupère un plan avec toutes ses relations en une seule requête
     */
    public function findWithRelations(int $id): ?PlanActions
    {
        return $this->createQueryBuilder('p')
            ->select('p', 's') // Sélectionne le plan ET la sortieAI
            ->leftJoin('p.sortieAI', 's')
            ->addSelect('s') // Charge s dans la même requête (évite N+1)
            ->where('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Version ultra-rapide avec SQL direct
     */
    public function findForDetailView(int $id): ?array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = "
            SELECT 
                p.id,
                p.decision,
                p.description,
                p.statut,
                DATE_FORMAT(p.created_at, '%d/%m/%Y %H:%i') as created_at_fr,
                DATE_FORMAT(p.updated_at, '%d/%m/%Y %H:%i') as updated_at_fr,
                s.contenu as sortie_contenu,
                s.categorie_sortie,
                s.criticite
            FROM plan_actions p
            LEFT JOIN sortie_ai s ON p.sortie_ai_id = s.id
            WHERE p.id = ?
            LIMIT 1
        ";
        
        return $conn->executeQuery($sql, [$id])->fetchAssociative();
    }
    
    /**
     * Compte les plans (version optimisée)
     */
    public function countAll(): int
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $result = $conn->executeQuery("SELECT COUNT(*) as count FROM plan_actions")
                      ->fetchAssociative();
        
        return (int) ($result['count'] ?? 0);
    }
}