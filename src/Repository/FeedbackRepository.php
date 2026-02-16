<?php

namespace App\Repository;

use App\Entity\Feedback;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FeedbackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Feedback::class);
    }

    /**
     * Search feedbacks by message content with optional sorting
     * 
     * @param Utilisateur $user The user whose feedbacks to search
     * @param string|null $searchTerm The search term for message content (case-insensitive, partial match)
     * @param string $sortOrder Sort order: 'DESC' for newest first, 'ASC' for oldest first
     * @return Feedback[] Returns an array of Feedback objects
     */
    public function searchByUser(Utilisateur $user, ?string $searchTerm = null, string $sortOrder = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('f')
            ->where('f.utilisateur = :user')
            ->setParameter('user', $user);

        // Add search filter if search term is provided
        if ($searchTerm !== null && trim($searchTerm) !== '') {
            $qb->andWhere('LOWER(f.contenu) LIKE LOWER(:searchTerm)')
               ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        // Add sorting by date
        $qb->orderBy('f.datefeedback', $sortOrder);

        return $qb->getQuery()->getResult();
    }
}
