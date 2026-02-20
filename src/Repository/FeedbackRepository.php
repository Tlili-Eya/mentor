<?php

namespace App\Repository;

use App\Entity\Feedback;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Feedback>
 */
class FeedbackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Feedback::class);
    }

    /**
     * Find feedbacks by state with explicit field selection
     */
    public function findByStateWithContent(string $state): array
    {
        return $this->createQueryBuilder('f')
            ->select('f.id', 'f.contenu', 'f.note', 'f.datefeedback', 'f.typefeedback', 'f.etatfeedback')
            ->addSelect('u.id as user_id', 'u.nom', 'u.prenom', 'u.email')
            ->addSelect('t.id as traitement_id', 't.typetraitement', 't.description', 't.datetraitement')
            ->leftJoin('f.utilisateur', 'u')
            ->leftJoin('f.traitement', 't')
            ->where('f.etatfeedback = :state')
            ->setParameter('state', $state)
            ->orderBy('f.datefeedback', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all feedbacks by state (standard method)
     */
    public function findByState(string $state): array
    {
        return $this->createQueryBuilder('f')
            ->leftJoin('f.utilisateur', 'u')
            ->leftJoin('f.traitement', 't')
            ->where('f.etatfeedback = :state')
            ->setParameter('state', $state)
            ->orderBy('f.datefeedback', 'DESC')
            ->getQuery()
            ->getResult();
    }
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
