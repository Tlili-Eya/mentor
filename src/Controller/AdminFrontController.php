<?php

namespace App\Controller;

use App\Repository\PlanActionsRepository;
use App\Repository\ReferenceArticleRepository;
use App\Repository\SortieAIRepository;
use App\Repository\CategorieArticleRepository;
use App\Entity\Reclamation;
use App\Form\ReclamationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminFrontController extends AbstractController
{
    #[Route('/dashboard', name: 'app_admin_dashboard', methods: ['GET'])]
    public function dashboard(
        PlanActionsRepository $planRepository,
        SortieAIRepository $aiRepository
    ): Response
    {
        // Statistiques pour le dashboard
        $stats = [
            'total_plans' => $planRepository->count([]),
            'plans_en_cours' => $planRepository->count(['statut' => \App\Enum\Statut::EnCours]),
            'plans_termines' => $planRepository->count(['statut' => \App\Enum\Statut::Fini]),
            'plans_en_attente' => $planRepository->count(['statut' => \App\Enum\Statut::EnAttente]),
            'alertes_ia' => $aiRepository->count(['typeSortie' => \App\Enum\TypeSortie::Alerte]),
            'predictions_ia' => $aiRepository->count(['typeSortie' => \App\Enum\TypeSortie::Prediction]),
            'recommandations_ia' => $aiRepository->count(['typeSortie' => \App\Enum\TypeSortie::Recommandation]),
        ];

        // Plans d'action récents
        $recentPlans = $planRepository->findBy(
            [],
            ['updatedAt' => 'DESC'],
            5
        );

        // Alertes critiques récentes
        $alertesCritiques = $aiRepository->findBy(
            ['criticite' => \App\Enum\Criticite::Eleve],
            ['updatedAt' => 'DESC'],
            3
        );

        return $this->render('front/admin/dashboard.html.twig', [
            'stats' => $stats,
            'recentPlans' => $recentPlans,
            'alertesCritiques' => $alertesCritiques,
        ]);
    }
#[Route('/plans', name: 'app_admin_plans', methods: ['GET'])]
public function plans(
    Request $request,
    EntityManagerInterface $em
): Response
{
    $search = $request->query->get('search', '');
    $categorie = $request->query->get('categorie', '');
    $statut = $request->query->get('statut', '');
    $page = max(1, $request->query->getInt('page', 1));
    $limit = 9;

    $conn = $em->getConnection();
    
    // Base SQL
    $sql = "
        SELECT 
            p.id,
            p.decision,
            p.description,
            p.statut,
            p.date as createdAt,
            p.updated_at as updatedAt,
            p.categorie as plan_categorie,
            s.id as sortie_id,
            s.categorie_sortie as sortie_categorie
        FROM plan_actions p
        LEFT JOIN sortie_ai s ON p.sortie_ai_id = s.id
        WHERE 1=1
    ";
    
    $params = [];
    $paramTypes = [];
    
    // Conditions
    if (!empty($search)) {
        $sql .= " AND (p.decision LIKE ? OR p.description LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $paramTypes[] = \PDO::PARAM_STR;
        $paramTypes[] = \PDO::PARAM_STR;
    }
    
    if (!empty($categorie)) {
        $sql .= " AND (p.categorie = ? OR s.categorie_sortie = ?)";
        $params[] = $categorie;
        $params[] = $categorie;
        $paramTypes[] = \PDO::PARAM_STR;
        $paramTypes[] = \PDO::PARAM_STR;
    }
    
    if (!empty($statut)) {
        $sql .= " AND p.statut = ?";
        $params[] = $statut;
        $paramTypes[] = \PDO::PARAM_STR;
    }
    
    // Ordre
    $sql .= " ORDER BY p.updated_at DESC, p.date DESC";
    
    // 1. Compter
    $countSql = "SELECT COUNT(*) FROM (" . str_replace(
        ['p.id, p.decision, p.description, p.statut, p.date as createdAt, p.updated_at as updatedAt, p.categorie as plan_categorie, s.id as sortie_id, s.categorie_sortie as sortie_categorie'],
        ['1'],
        $sql
    ) . ") as counted";
    
    $total = $conn->executeQuery($countSql, $params, $paramTypes)->fetchOne();
    
    // 2. Pagination - APPROCHE SÉCURISÉE
    $offset = ($page - 1) * $limit;
    
    // IMPORTANT: Concaténation directe avec validation numérique
    $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
    
    // 3. Exécuter
    $plansData = $conn->executeQuery($sql, $params, $paramTypes)->fetchAllAssociative();
    
    // Conversion
    $plans = [];
    foreach ($plansData as $data) {
        $plans[] = [
            'id' => $data['id'],
            'decision' => $data['decision'],
            'description' => $data['description'],
            'statut' => $data['statut'],
            'date' => $data['createdAt'] ? new \DateTime($data['createdAt']) : null,
            'updatedAt' => $data['updatedAt'] ? new \DateTime($data['updatedAt']) : null,
            'categorie' => $data['plan_categorie'],
            'sortieAI' => $data['sortie_id'] ? [
                'id' => $data['sortie_id'],
                'categorieSortie' => $data['sortie_categorie'],
            ] : null,
        ];
    }
    
    $totalPages = ceil($total / $limit);
    
    return $this->render('front/admin/plans.html.twig', [
        'plans' => $plans,
        'search' => $search,
        'categorie' => $categorie,
        'statut' => $statut,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'total' => $total,
    ]);
}
#[Route('/plan/{id}', name: 'app_admin_plan_detail', methods: ['GET'])]
public function planDetail(
    int $id, 
    EntityManagerInterface $em
): Response
{
    // Validation
    if ($id <= 0) {
        $this->addFlash('error', 'ID de plan invalide.');
        return $this->redirectToRoute('app_admin_plans');
    }
    
    try {
        $conn = $em->getConnection();
        
        // Vérifier existence
        $exists = (bool) $conn->executeQuery(
            "SELECT EXISTS(SELECT 1 FROM plan_actions WHERE id = ?) as exists_flag", 
            [$id]
        )->fetchOne();
        
        if (!$exists) {
            $availableIds = $conn->executeQuery(
                "SELECT id FROM plan_actions ORDER BY id LIMIT 20"
            )->fetchFirstColumn();
            
            return $this->render('front/admin/plan_not_found.html.twig', [
                'id' => $id,
                'availableIds' => $availableIds,
            ]);
        }
        
        // Charger avec les BONS noms de colonnes
        $sql = "
            SELECT 
                p.id,
                p.decision,
                p.description,
                p.statut,
                p.date as createdAt,       -- ICI: date
                p.updated_at as updatedAt, -- ICI: updated_at
                p.categorie as plan_categorie,
                p.feedback_enseignant,
                p.feedback_date,
                
                -- Sortie AI
                s.id as sortie_id,
                s.contenu as sortie_contenu,
                s.type_sortie as sortie_type,
                s.categorie_sortie as sortie_categorie,
                s.criticite as sortie_criticite,
                s.created_at as sortie_createdAt
                
            FROM plan_actions p
            LEFT JOIN sortie_ai s ON p.sortie_ai_id = s.id
            WHERE p.id = ?
            LIMIT 1
        ";
        
        $planData = $conn->executeQuery($sql, [$id])->fetchAssociative();
        
        if (!$planData) {
            throw new \Exception("Erreur lors du chargement du plan #{$id}");
        }
        
        // Préparer les données
        $plan = [
            'id' => $planData['id'],
            'decision' => $planData['decision'] ?? 'Sans titre',
            'description' => $planData['description'] ?? 'Aucune description',
            'statut' => $planData['statut'] ?? 'INCONNU',
            'date' => $planData['createdAt'] ? new \DateTime($planData['createdAt']) : null,
            'updatedAt' => $planData['updatedAt'] ? new \DateTime($planData['updatedAt']) : null,
            'categorie' => $planData['plan_categorie'],
            'feedbackEnseignant' => $planData['feedback_enseignant'],
            'feedbackDate' => $planData['feedback_date'] ? new \DateTime($planData['feedback_date']) : null,
            
            'sortieAI' => $planData['sortie_id'] ? [
                'id' => $planData['sortie_id'],
                'contenu' => $planData['sortie_contenu'],
                'typeSortie' => $planData['sortie_type'],
                'categorieSortie' => $planData['sortie_categorie'],
                'criticite' => $planData['sortie_criticite'],
                'createdAt' => new \DateTime($planData['sortie_createdAt']),
            ] : null,
        ];
        
        return $this->render('front/admin/plan_detail.html.twig', [
            'plan' => $plan,
        ]);
        
    } catch (\Exception $e) {
        error_log("Admin Plan Detail ERROR ID {$id}: " . $e->getMessage());
        
        $this->addFlash('error', "Erreur lors du chargement du plan #{$id}.");
        return $this->redirectToRoute('app_admin_plans');
    }
}
    #[Route('/articles', name: 'app_admin_articles', methods: ['GET'])]
    public function articles(
        Request $request,
        ReferenceArticleRepository $articleRepository,
        CategorieArticleRepository $categorieRepository
    ): Response
    {
        // Version SÉCURISÉE sans instanciation directe de controller
        try {
            // Récupérer les articles avec pagination simple
            $page = max(1, $request->query->getInt('page', 1));
            $limit = 12;
            
            // Recherche
            $search = $request->query->get('search', '');
            $categorie = $request->query->get('categorie', null);
            
            $qb = $articleRepository->createQueryBuilder('a')
                ->orderBy('a.createdAt', 'DESC');
            
            if (!empty($search)) {
                $qb->andWhere('a.titre LIKE :search OR a.contenu LIKE :search')
                   ->setParameter('search', '%' . $search . '%');
            }
            
            if ($categorie) {
                $qb->andWhere('a.categorie = :categorie')
                   ->setParameter('categorie', $categorie);
            }
            
            // Pagination
            $total = count($qb->getQuery()->getResult());
            $articles = $qb->setFirstResult(($page - 1) * $limit)
                          ->setMaxResults($limit)
                          ->getQuery()
                          ->getResult();
            
            $totalPages = ceil($total / $limit);
            $categories = $categorieRepository->findAll();
            
            return $this->render('front/admin/articles.html.twig', [
                'articles' => $articles,
                'categories' => $categories,
                'search' => $search,
                'categorie' => $categorie,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'total' => $total,
            ]);
            
        } catch (\Exception $e) {
            // En cas d'erreur, afficher une liste vide
            return $this->render('front/admin/articles.html.twig', [
                'articles' => [],
                'categories' => [],
                'search' => '',
                'categorie' => null,
                'currentPage' => 1,
                'totalPages' => 1,
                'total' => 0,
            ]);
        }
    }

    #[Route('/chat-ia', name: 'app_admin_chat_ia', methods: ['GET'])]
    public function chatIA(): Response
    {
        return $this->render('front/admin/chat_ia.html.twig');
    }

    #[Route('/reclamations', name: 'app_admin_reclamations', methods: ['GET'])]
    public function reclamations(EntityManagerInterface $entityManager): Response
    {
        $reclamations = $entityManager->getRepository(Reclamation::class)->findBy(
            [],
            ['updatedAt' => 'DESC']
        );

        return $this->render('front/admin/reclamations.html.twig', [
            'reclamations' => $reclamations,
        ]);
    }
}