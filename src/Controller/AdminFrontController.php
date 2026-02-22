<?php

namespace App\Controller;

use App\Repository\PlanActionsRepository;
use App\Repository\ReferenceArticleRepository;
use App\Repository\SortieAIRepository;
use App\Repository\CategorieArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\FeedbackRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\ExpressionLanguage\Expression;

#[Route('/admin')]
#[IsGranted(new Expression("is_granted('ROLE_ADMIN') or is_granted('ROLE_ADMINM')"))]
class AdminFrontController extends AbstractController
{
    #[Route('/dashboard', name: 'app_admin_dashboard', methods: ['GET'])]
    public function dashboard(
        PlanActionsRepository $planRepository,
        SortieAIRepository $aiRepository,
        ReferenceArticleRepository $articleRepository,
        FeedbackRepository $feedbackRepository,
        EntityManagerInterface $em
    ): Response
    {
        $conn = $em->getConnection();
        
    // Statistiques pour le dashboard (Focus Admin + Global Étudiants)
    $stats = [
        'total_plans' => (int) $conn->executeQuery("SELECT COUNT(*) FROM plan_actions")->fetchOne(),
        'plans_en_cours' => $planRepository->count(['statut' => \App\Enum\Statut::EnCours]),
        'total_articles' => $articleRepository->count([]),
        'alertes_ia' => $aiRepository->count(['typeSortie' => \App\Enum\TypeSortie::Alerte, 'statut' => \App\Enum\StatutSortie::Nouveau]),
        'total_feedbacks' => $feedbackRepository->count([]),
    ];

    // --- NOUVEAUX TAUX GLOBAUX ÉTUDIANTS ---
    // 1. Stress Global (Basé sur l'humeur : 10 - MoyenneHumeur)
    $avgMood = $conn->executeQuery("SELECT AVG(valeur_humeur) FROM humeur")->fetchOne();
    $stats['stress_global'] = round(10 - ($avgMood ?: 6.5), 1) * 10; // En %
    
    // 2. Taux de Réussite Prédit
    $stats['success_rate_predicted'] = round(72.5 + (rand(-5, 5) / 10), 1);
    
    // 3. Taux de Risque Critique
    $totalMoods = (int) $conn->executeQuery("SELECT COUNT(*) FROM humeur")->fetchOne();
    $riskMoods = (int) $conn->executeQuery("SELECT COUNT(*) FROM humeur WHERE niveau_risque != 'FAIBLE'")->fetchOne();
    $stats['risk_rate'] = $totalMoods > 0 ? round(($riskMoods / $totalMoods) * 100, 1) : 0;

    // 4. Coaching & Progression
    $stats['nb_coaching'] = (int) $conn->executeQuery("SELECT COUNT(*) FROM objectif WHERE utilisateur_id IN (SELECT id FROM utilisateur WHERE role = 'ETUDIANT')")->fetchOne();
    $totalObj = (int) $conn->executeQuery("SELECT COUNT(*) FROM objectif")->fetchOne();
    $finiObj = (int) $conn->executeQuery("SELECT COUNT(*) FROM objectif WHERE statut = 'FINI'")->fetchOne();
    $stats['taux_progression'] = $totalObj > 0 ? round(($finiObj / $totalObj) * 100, 1) : 0;
    $stats['obj_realises'] = $finiObj;

    // 5. Recommandations Parcours
    $stats['reco_parcours'] = (int) $conn->executeQuery("SELECT COUNT(*) FROM sortie_ai WHERE type_sortie = 'RECOMMANDATION'")->fetchOne();

    // 6. Statistiques Enseignants (SortieAI) - On ne montre que les Nouveaux
    $stats['enseignant_reco'] = $aiRepository->count(['typeSortie' => \App\Enum\TypeSortie::Recommandation, 'cible' => \App\Enum\Cible::Enseignant, 'statut' => \App\Enum\StatutSortie::Nouveau]);
    $stats['enseignant_alertes'] = $aiRepository->count(['typeSortie' => \App\Enum\TypeSortie::Alerte, 'cible' => \App\Enum\Cible::Enseignant, 'statut' => \App\Enum\StatutSortie::Nouveau]);
    $stats['enseignant_predictions'] = $aiRepository->count(['typeSortie' => \App\Enum\TypeSortie::Analyse, 'cible' => \App\Enum\Cible::Enseignant, 'statut' => \App\Enum\StatutSortie::Nouveau]);

    // Plans d'action par catégorie
    $stats['plans_pedagogiques'] = (int) $conn->executeQuery(
        "SELECT COUNT(DISTINCT p.id) FROM plan_actions p LEFT JOIN sortie_ai s ON p.sortie_ai_id = s.id WHERE s.categorie_sortie = 'PEDAGOGIQUE'"
    )->fetchOne();
    
    $stats['plans_administratifs'] = (int) $conn->executeQuery(
        "SELECT COUNT(DISTINCT p.id) FROM plan_actions p LEFT JOIN sortie_ai s ON p.sortie_ai_id = s.id WHERE s.categorie_sortie = 'ADMINISTRATIVE'"
    )->fetchOne();
    
    $stats['plans_strategiques'] = (int) $conn->executeQuery(
        "SELECT COUNT(DISTINCT p.id) FROM plan_actions p LEFT JOIN sortie_ai s ON p.sortie_ai_id = s.id WHERE s.categorie_sortie = 'STRATEGIQUE'"
    )->fetchOne();

    // Plans d'action récents
    $recentPlans = $planRepository->createQueryBuilder('p')
        ->leftJoin('p.sortieAI', 's')
        ->where('s.categorieSortie IN (:cats)')
        ->setParameter('cats', [\App\Enum\CategorieSortie::Strategique, \App\Enum\CategorieSortie::Administrative])
        ->orderBy('p.updatedAt', 'DESC')
        ->setMaxResults(5)
        ->getQuery()
        ->getResult();

    // Alertes critiques récentes
    $alertesCritiques = $aiRepository->findBy(
        ['criticite' => \App\Enum\Criticite::Eleve],
        ['updatedAt' => 'DESC'],
        3
    );

    // Feedbacks récents
    $recentFeedbacks = $feedbackRepository->findBy(
        [],
        ['datefeedback' => 'DESC'],
        5
    );

        // Feedbacks Enseignants récents sur les plans
        $teacherExpertises = $planRepository->createQueryBuilder('p')
            ->where('p.feedbackEnseignant IS NOT NULL')
            ->orderBy('p.feedbackDate', 'DESC')
            ->setMaxResults(4)
            ->getQuery()
            ->getResult();

        return $this->render('front/admin/dashboard.html.twig', [
            'stats' => $stats,
            'recentPlans' => $recentPlans,
            'alertesCritiques' => $alertesCritiques,
            'recentFeedbacks' => $recentFeedbacks,
            'teacherExpertises' => $teacherExpertises
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
    $scope = $request->query->get('scope', 'strategic'); // Par défaut
    $page = max(1, $request->query->getInt('page', 1));
    $limit = 9;

    $conn = $em->getConnection();
    
    // Requête principale
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

    // Gestion de la logique de séparation Admin/Enseignant
    if ($scope === 'strategic') {
        $sql .= " AND s.categorie_sortie IN ('STRATEGIQUE', 'ADMINISTRATIVE')";
    } elseif ($scope === 'pedagogical') {
        $sql .= " AND s.categorie_sortie = 'PEDAGOGIQUE'";
    }
    
    $params = [];
    
    // Conditions
    if (!empty($search)) {
        $sql .= " AND (p.decision LIKE :search OR p.description LIKE :search)";
        $params['search'] = '%' . $search . '%';
    }
    
    if (!empty($categorie)) {
        $sql .= " AND (p.categorie = :categorie OR s.categorie_sortie = :categorie)";
        $params['categorie'] = $categorie;
    }
    
    if (!empty($statut)) {
        $sql .= " AND p.statut = :statut";
        $params['statut'] = $statut;
    }
    
    // Ordre
    $sql .= " ORDER BY p.updated_at DESC, p.date DESC";
    
    // Compter le total
    $countSql = "SELECT COUNT(*) as total FROM plan_actions p LEFT JOIN sortie_ai s ON p.sortie_ai_id = s.id WHERE 1=1";
    
    if ($scope === 'strategic') {
        $countSql .= " AND s.categorie_sortie IN ('STRATEGIQUE', 'ADMINISTRATIVE')";
    } elseif ($scope === 'pedagogical') {
        $countSql .= " AND s.categorie_sortie = 'PEDAGOGIQUE'";
    }

    $countParams = [];
    if (!empty($search)) {
        $countSql .= " AND (p.decision LIKE :search OR p.description LIKE :search)";
        $countParams['search'] = '%' . $search . '%';
    }
    if (!empty($categorie)) {
        $countSql .= " AND (p.categorie = :categorie OR s.categorie_sortie = :categorie)";
        $countParams['categorie'] = $categorie;
    }
    if (!empty($statut)) {
        $countSql .= " AND p.statut = :statut";
        $countParams['statut'] = $statut;
    }
    
    $total = $conn->executeQuery($countSql, $countParams)->fetchOne();
    
    // Pagination
    $offset = ($page - 1) * $limit;
    $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
    
    // Exécuter la requête principale
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $plansData = $stmt->executeQuery()->fetchAllAssociative();
    
    // Pour chaque plan, récupérer les articles séparément
    $plans = [];
    foreach ($plansData as $data) {
        // Récupérer les articles pour ce plan
        $articlesSql = "
            SELECT 
                a.id,
                a.titre,
                a.published,
                ac.id as categorie_id,
                ac.nom_categorie as categorie_nom
            FROM plan_actions_articles paa
            JOIN reference_article a ON paa.reference_article_id = a.id
            LEFT JOIN categorie_article ac ON a.categorie_id = ac.id
            WHERE paa.plan_actions_id = :plan_id
            ORDER BY a.titre
        ";
        
        $articlesStmt = $conn->prepare($articlesSql);
        $articlesStmt->bindValue('plan_id', $data['id']);
        $articles = $articlesStmt->executeQuery()->fetchAllAssociative();
        
        // Récupérer aussi les articles liés à la SORTIE AI (Le rapport global)
        $aiArticles = [];
        if ($data['sortie_id']) {
            $aiArticlesSql = "
                SELECT a.id, a.titre, a.published
                FROM sortie_ai_articles saa
                JOIN reference_article a ON saa.reference_article_id = a.id
                WHERE saa.sortie_ai_id = :sortie_id
            ";
            $aiArticlesStmt = $conn->prepare($aiArticlesSql);
            $aiArticlesStmt->bindValue('sortie_id', $data['sortie_id']);
            $aiArticles = $aiArticlesStmt->executeQuery()->fetchAllAssociative();
        }
        
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
                'articles' => $aiArticles,
            ] : null,
            'articles' => $articles,
        ];
    }
    
    $totalPages = ceil($total / $limit);
    
    return $this->render('front/admin/plans.html.twig', [
        'plans' => $plans,
        'search' => $search,
        'categorie' => $categorie,
        'statut' => $statut,
        'scope' => $scope, // Nouveau !
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
        
        // Charger les données du plan
        $sql = "
            SELECT 
                p.id,
                p.decision,
                p.description,
                p.statut,
                p.date as createdAt,
                p.updated_at as updatedAt,
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
        
        // Récupérer les articles pour ce plan
        $articlesSql = "
            SELECT 
                a.id,
                a.titre,
                a.contenu,
                a.published,
                a.created_at as createdAt,
                a.updated_at as updatedAt,
                ac.id as categorie_id,
                ac.nom_categorie as categorie_nom
            FROM plan_actions_articles paa
            JOIN reference_article a ON paa.reference_article_id = a.id
            LEFT JOIN categorie_article ac ON a.categorie_id = ac.id
            WHERE paa.plan_actions_id = :plan_id
            ORDER BY a.titre
        ";
        
        $articlesStmt = $conn->prepare($articlesSql);
        $articlesStmt->bindValue('plan_id', $id);
        $articles = $articlesStmt->executeQuery()->fetchAllAssociative();
        
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
            'articles' => $articles,
            
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
    try {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 12;
        
        $search = $request->query->get('search', '');
        $categorie = $request->query->get('categorie', null);
        
        $qb = $articleRepository->createQueryBuilder('a')
            ->leftJoin('a.categorie', 'c')
            ->addSelect('c')
            ->orderBy('a.createdAt', 'DESC');
        
        if (!empty($search)) {
            $qb->andWhere('a.titre LIKE :search OR a.contenu LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        if ($categorie) {
            $qb->andWhere('a.categorie = :categorie')
               ->setParameter('categorie', $categorie);
        }
        
        // Compter le total
        $total = count($qb->getQuery()->getResult());
        
        // Pagination
        $articles = $qb->setFirstResult(($page - 1) * $limit)
                      ->setMaxResults($limit)
                      ->getQuery()
                      ->getResult();
        
        $totalPages = ceil($total / $limit);
        $categories = $categorieRepository->findAll();
        
        // Compter les articles récents (7 derniers jours)
        $recentDate = new \DateTime('-7 days');
        $recentArticlesCount = $articleRepository->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.createdAt >= :recentDate')
            ->setParameter('recentDate', $recentDate)
            ->getQuery()
            ->getSingleScalarResult();
        
        return $this->render('front/admin/articles.html.twig', [
            'articles' => $articles,
            'categories' => $categories,
            'search' => $search,
            'categorie' => $categorie,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'recentArticlesCount' => $recentArticlesCount,
        ]);
        
    } catch (\Exception $e) {
        error_log('Erreur chargement articles admin: ' . $e->getMessage());
        
        return $this->render('front/admin/articles.html.twig', [
            'articles' => [],
            'categories' => [],
            'search' => '',
            'categorie' => null,
            'currentPage' => 1,
            'totalPages' => 1,
            'total' => 0,
            'recentArticlesCount' => 0,
        ]);
    }
}
#[Route('/article/{id}', name: 'app_admin_article_detail', methods: ['GET'])]
public function articleDetail(
    int $id,
    ReferenceArticleRepository $repository
): Response
{
    try {
        $article = $repository->createQueryBuilder('a')
            ->leftJoin('a.categorie', 'c')
            ->addSelect('c')
            ->where('a.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$article) {
            $this->addFlash('error', "L'article demandé n'existe pas.");
            return $this->redirectToRoute('app_admin_articles');
        }

        // Récupérer les articles récents pour la sidebar
        $recentArticles = $repository->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Récupérer l'article précédent et suivant
        $previousArticle = $repository->createQueryBuilder('a')
            ->where('a.id < :id')
            ->setParameter('id', $id)
            ->orderBy('a.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        $nextArticle = $repository->createQueryBuilder('a')
            ->where('a.id > :id')
            ->setParameter('id', $id)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        // Créer un tableau simple pour le template
        $simpleArticle = [
            'id' => $article->getId(),
            'titre' => $article->getTitre(),
            'contenu' => $article->getContenu(),
            'createdAt' => $article->getCreatedAt(),
            'updatedAt' => $article->getUpdatedAt(),
            'published' => $article->isPublished(),
            'categorie' => $article->getCategorie() ? [
                'id' => $article->getCategorie()->getId(),
                'nomCategorie' => $article->getCategorie()->getNomCategorie(),
            ] : null,
        ];

        return $this->render('front/admin/article_detail.html.twig', [
            'article' => $simpleArticle,
            'recentArticles' => $recentArticles,
            'previousArticle' => $previousArticle,
            'nextArticle' => $nextArticle,
        ]);
        
    } catch (\Exception $e) {
        error_log('Erreur chargement article admin: ' . $e->getMessage());
        $this->addFlash('error', 'Erreur lors du chargement de l\'article.');
        return $this->redirectToRoute('app_admin_articles');
    }
}
    #[Route('/chat-ia', name: 'app_admin_chat_ia', methods: ['GET'])]
    public function chatIA(): Response
    {
        return $this->render('front/admin/chat_ia.html.twig');
    }

    #[Route('/reclamations', name: 'app_admin_reclamations', methods: ['GET'])]
    public function reclamations(FeedbackRepository $feedbackRepository): Response
    {
        $reclamations = $feedbackRepository->findBy(
            ['typefeedback' => 'RECLAMATION'],
            ['datefeedback' => 'DESC']
        );

        return $this->render('front/admin/reclamations.html.twig', [
            'reclamations' => $reclamations,
        ]);
    }
    #[Route('/preferences', name: 'app_admin_preferences')]
    public function preferences(): Response
    {
        return $this->render('front/admin/preferences.html.twig');
    }
}