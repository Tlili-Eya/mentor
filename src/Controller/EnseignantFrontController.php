<?php

namespace App\Controller;

use App\Repository\PlanActionsRepository;
use App\Repository\ReferenceArticleRepository;
use App\Repository\CategorieArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EnseignantFrontController extends AbstractController
{
    #[Route('/enseignant/dashboard', name: 'app_enseignant_dashboard')]
public function dashboard(EntityManagerInterface $em): Response
{
    $conn = $em->getConnection();
    
    // Statistiques avec requêtes SQL directes (beaucoup plus rapides)
    $stats = [
        // Articles publiés
        'total_articles' => (int) $conn->executeQuery(
            "SELECT COUNT(*) FROM reference_article WHERE published = 1"
        )->fetchOne(),
        
        // Plans pédagogiques
        'plans_pedagogiques' => (int) $conn->executeQuery(
            "SELECT COUNT(DISTINCT p.id) 
             FROM plan_actions p 
             LEFT JOIN sortie_ai s ON p.sortie_ai_id = s.id 
             WHERE s.categorie_sortie = 'PEDAGOGIQUE'"
        )->fetchOne(),
        
        // Plans administratifs
        'plans_administratifs' => (int) $conn->executeQuery(
            "SELECT COUNT(DISTINCT p.id) 
             FROM plan_actions p 
             LEFT JOIN sortie_ai s ON p.sortie_ai_id = s.id 
             WHERE s.categorie_sortie = 'ADMINISTRATIVE'"
        )->fetchOne(),
    ];
    
    // Pour les enseignants, articles pédagogiques = tous les articles publiés
    $stats['articles_pedagogiques'] = $stats['total_articles'];
    
    return $this->render('front/enseignant/dashboard.html.twig', [
        'total_articles' => $stats['total_articles'],
        'articles_pedagogiques' => $stats['articles_pedagogiques'],
        'plans_pedagogiques' => $stats['plans_pedagogiques'],
        'plans_administratifs' => $stats['plans_administratifs'],
    ]);
}

    #[Route('/articles', name: 'app_enseignant_articles', methods: ['GET'])]
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
            
            return $this->render('front/enseignant/articles.html.twig', [
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
            return $this->render('front/enseignant/articles.html.twig', [
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

    ##[Route('/article/{id}', name: 'app_article_detail', methods: ['GET'])]
public function detail(
    int $id,
    ReferenceArticleRepository $repository,
    EntityManagerInterface $em
): Response
{
    // OPTIMISATION 1: Désactiver le profiler pour les tests
    if (function_exists('xdebug_disable')) {
        xdebug_disable();
    }

    // OPTIMISATION 2: Utiliser une requête optimisée
    try {
        $article = $repository->createQueryBuilder('a')
            ->leftJoin('a.categorie', 'c')
            ->addSelect('c') // Important: pour éviter le N+1
            ->where('a.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$article) {
            throw $this->createNotFoundException('Article non trouvé');
        }

        // Vérifier la publication
        if (!$article->isPublished() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException('Cet article n\'est pas disponible.');
        }

        // OPTIMISATION 3: Limiter les données
        $simpleArticle = [
            'id' => $article->getId(),
            'titre' => $article->getTitre(),
            'contenu' => $article->getContenu(),
            'image' => $article->getImage(),
            'resume' => $article->getResume(),
            'createdAt' => $article->getCreatedAt(),
            'updatedAt' => $article->getUpdatedAt(),
            'categorie' => $article->getCategorie() ? [
                'id' => $article->getCategorie()->getId(),
                'nom' => $article->getCategorie()->getNom(),
            ] : null,
        ];

        return $this->render('front/enseignant/article_detail.html.twig', [
            'article' => $simpleArticle, // Passer un tableau au lieu de l'entité
        ]);
        
    } catch (\Exception $e) {
        // Journaliser l'erreur
        error_log('Erreur chargement article: ' . $e->getMessage());
        throw $this->createNotFoundException('Erreur lors du chargement de l\'article');
    }
}

   #[Route('/plans', name: 'app_enseignant_plans', methods: ['GET'])]
public function plans(
    Request $request,
    EntityManagerInterface $em
): Response
{
    $startTime = microtime(true);
    
    $search = $request->query->get('search', '');
    $categorie = $request->query->get('categorie', '');
    $page = max(1, $request->query->getInt('page', 1));
    $limit = 9;

    $conn = $em->getConnection();
    
    // OPTIMISATION: Requête SQL directe
    $sql = "
        SELECT 
            p.id,
            p.decision,
            p.description,
            p.statut,
            p.date as createdAt,
            p.updated_at as updatedAt,
            s.id as sortie_id,
            s.categorie_sortie as sortie_categorie
        FROM plan_actions p
        LEFT JOIN sortie_ai s ON p.sortie_ai_id = s.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Conditions
    if (!empty($search)) {
        $sql .= " AND (p.decision LIKE ? OR p.description LIKE ?)";
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }
    
    if (!empty($categorie) && in_array($categorie, ['PEDAGOGIQUE', 'ADMINISTRATIVE', 'STRATEGIQUE'])) {
        $sql .= " AND s.categorie_sortie = ?";
        $params[] = $categorie;
    }
    
    // Tri par date
    $sql .= " ORDER BY p.updated_at DESC";
    
    // 1. Compter le total (optimisé)
    $countSql = "SELECT COUNT(*) FROM (" . str_replace(
        ['p.id, p.decision, p.description, p.statut, p.date as createdAt, p.updated_at as updatedAt, s.id as sortie_id, s.categorie_sortie as sortie_categorie'],
        ['1'],
        $sql
    ) . ") as counted";
    
    $total = $conn->executeQuery($countSql, $params)->fetchOne();
    
    // 2. Pagination - concaténation directe pour éviter les problèmes de types
    $offset = ($page - 1) * $limit;
    $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
    
    // 3. Exécuter la requête
    $startQuery = microtime(true);
    $plansData = $conn->executeQuery($sql, $params)->fetchAllAssociative();
    $queryTime = microtime(true) - $startQuery;
    
    error_log("Enseignant plans query: " . sprintf("%.3f", $queryTime) . "s");
    
    // 4. Convertir en tableau pour le template
    $plans = [];
    foreach ($plansData as $data) {
        $plans[] = [
            'id' => $data['id'],
            'decision' => $data['decision'],
            'description' => $data['description'],
            'statut' => $data['statut'],
            'date' => $data['createdAt'] ? new \DateTime($data['createdAt']) : null,
            'updatedAt' => $data['updatedAt'] ? new \DateTime($data['updatedAt']) : null,
            'sortieAI' => $data['sortie_id'] ? [
                'id' => $data['sortie_id'],
                'categorieSortie' => $data['sortie_categorie'],
            ] : null,
        ];
    }
    
    $totalPages = ceil($total / $limit);
    
    $totalTime = microtime(true) - $startTime;
    error_log("Enseignant plans TOTAL: " . sprintf("%.3f", $totalTime) . "s - " . count($plans) . " plans");
    
    return $this->render('front/enseignant/plans.html.twig', [
        'plans' => $plans,
        'search' => $search,
        'categorie' => $categorie,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'total' => $total,
    ]);
}
  #[Route('/plan/{id}', name: 'app_enseignant_plan_detail', methods: ['GET'])]
public function planDetail(int $id, EntityManagerInterface $em): Response
{
    // Validation
    if ($id <= 0) {
        $this->addFlash('error', 'ID de plan invalide.');
        return $this->redirectToRoute('app_enseignant_plans');
    }
    
    try {
        $conn = $em->getConnection();
        
        // Vérifier existence rapide
        $exists = (bool) $conn->executeQuery(
            "SELECT EXISTS(SELECT 1 FROM plan_actions WHERE id = ?) as exists_flag", 
            [$id]
        )->fetchOne();
        
        if (!$exists) {
            // Plan non trouvé, afficher des suggestions
            $availableIds = $conn->executeQuery(
                "SELECT id, decision FROM plan_actions ORDER BY updated_at DESC LIMIT 5"
            )->fetchAllAssociative();
            
            return $this->render('front/enseignant/plan_error.html.twig', [
                'id' => $id,
                'error' => 'Le plan demandé n\'a pas été trouvé.',
                'suggestions' => $availableIds,
            ]);
        }
        
        // Charger les données avec requête optimisée
        $sql = "
            SELECT 
                p.id,
                p.decision,
                p.description,
                p.statut,
                p.date as createdAt,
                p.updated_at as updatedAt,
                
                -- Sortie AI
                s.id as sortie_id,
                s.contenu as sortie_contenu,
                s.categorie_sortie as sortie_categorie
                
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
            
            'sortieAI' => $planData['sortie_id'] ? [
                'id' => $planData['sortie_id'],
                'contenu' => $planData['sortie_contenu'],
                'categorieSortie' => $planData['sortie_categorie'],
            ] : null,
        ];
        
        return $this->render('front/enseignant/plan_detail.html.twig', [
            'plan' => $plan,
        ]);
        
    } catch (\Exception $e) {
        error_log("Enseignant Plan Detail ERROR ID {$id}: " . $e->getMessage());
        
        $this->addFlash('error', "Erreur lors du chargement du plan #{$id}.");
        return $this->redirectToRoute('app_enseignant_plans');
    }
}
    #[Route('/reclamation/nouvelle', name: 'app_enseignant_reclamation_new', methods: ['GET'])]
    public function nouvelleReclamation(): Response
    {
        // Fonctionnalité temporairement désactivée
        $this->addFlash('info', 'La fonctionnalité de réclamation est temporairement désactivée. Elle sera disponible prochainement.');
        return $this->redirectToRoute('app_enseignant_dashboard');
    }

    #[Route('/reclamation/plan/{planId}', name: 'app_enseignant_reclamation_plan', methods: ['GET'])]
    public function reclamationPourPlan(int $planId): Response
    {
        // Fonctionnalité temporairement désactivée
        $this->addFlash('info', 'La fonctionnalité de réclamation pour les plans est temporairement désactivée. Elle sera disponible prochainement.');
        return $this->redirectToRoute('app_enseignant_plan_detail', ['id' => $planId]);
    }

    #[Route('/chat-ia', name: 'app_enseignant_chat_ia', methods: ['GET'])]
    public function chatIA(): Response
    {
        return $this->render('front/enseignant/chat_ia.html.twig');
    }

    #[Route('/statistiques', name: 'app_enseignant_stats', methods: ['GET'])]
    public function statistiques(
        PlanActionsRepository $planRepository,
        ReferenceArticleRepository $articleRepository
    ): Response
    {
        $user = $this->getUser();
        
        $stats = [
            'articles_lus' => 0,
            'plans_consultes' => 0,
            'reclamations_envoyees' => 0,
        ];

        // Pour l'instant, toutes les statistiques sont à 0
        // Vous pourrez implémenter ces fonctionnalités plus tard
        
        return $this->render('front/enseignant/statistiques.html.twig', [
            'stats' => $stats,
            'user' => $user,
        ]);
    }
    #[Route('/reclamations', name: 'app_enseignant_reclamations', methods: ['GET'])]
public function reclamations(): Response
{
    // Version temporaire - rediriger vers le dashboard
    $this->addFlash('info', 'La page des réclamations sera disponible prochainement.');
    return $this->redirectToRoute('app_enseignant_dashboard');
}
}